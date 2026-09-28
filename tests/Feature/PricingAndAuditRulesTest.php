<?php

namespace Tests\Feature;

use App\Actions\Pricing\CalculateEnrollmentPrice;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Regla 1 (un estudiante necesita una matrícula activa para pertenecer a un
 * nivel), Regla 5 (un pago histórico no cambia aunque cambien las
 * promociones) y Regla 14 (los registros importantes identifican quién los
 * creó y cuándo) de la sección 40.
 */
class PricingAndAuditRulesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_student_belongs_to_a_level_only_through_an_enrollment(): void
    {
        $student = Student::factory()->create();

        $this->assertCount(0, $student->enrollments);

        $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

        $this->assertTrue($student->fresh()->enrollments->contains($enrollment));
        $this->assertInstanceOf(Level::class, $enrollment->level);
    }

    public function test_historical_payment_amount_does_not_change_when_the_promotion_changes_later(): void
    {
        $level = Level::factory()->create(['price' => 300000]);
        $student = Student::factory()->create();
        $promotion = Promotion::factory()->create(['discount_type' => 'fijo', 'value' => 50000]);

        $pricing = app(CalculateEnrollmentPrice::class)->handle($level, $student, $promotion, null);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'base_amount' => $pricing['base_price'],
            'discount_amount' => $pricing['promotion_discount'],
            'final_amount' => $pricing['final_price'],
            'promotion_id' => $promotion->id,
            'status' => 'pagado',
        ]);

        $this->assertEquals(250000, $payment->final_amount);

        // La promoción cambia (o incluso desaparece) después.
        $promotion->update(['value' => 10000]);

        $this->assertEquals(250000, $payment->fresh()->final_amount);
    }

    public function test_critical_models_are_audited_with_the_acting_user_and_timestamp(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $enrollment = Enrollment::factory()->create();

        $log = AuditLog::where('auditable_type', Enrollment::class)
            ->where('auditable_id', $enrollment->id)
            ->where('action', 'creado')
            ->first();

        $this->assertNotNull($log, 'Se esperaba un registro de auditoría para la matrícula creada.');
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->name, $log->user_name);
        $this->assertNotNull($log->created_at);
    }

    public function test_admin_sets_the_enrollment_base_price_and_discounts_apply_to_it(): void
    {
        $level = Level::factory()->create(['price' => 300000]);
        $student = Student::factory()->create();
        $promotion = Promotion::factory()->create(['discount_type' => 'porcentaje', 'value' => 10]);

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'level_id' => $level->id,
                'enrolled_at' => '2026-09-28',
                'start_date' => '2026-09-28',
                'status' => 'activa',
                'required_hours' => 128,
                'weekly_hours' => 12,
                'base_price' => 450000,
                'promotion_id' => $promotion->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'base_price' => 450000,
            'promotion_discount' => 45000,
            'final_price' => 405000,
        ]);
    }

    public function test_enrollment_base_price_defaults_to_the_level_price(): void
    {
        $level = Level::factory()->create(['price' => 300000]);
        $student = Student::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'level_id' => $level->id,
                'enrolled_at' => '2026-09-28',
                'start_date' => '2026-09-28',
                'status' => 'activa',
                'required_hours' => 128,
                'weekly_hours' => 8,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'base_price' => 300000, 'final_price' => 300000]);
    }
}
