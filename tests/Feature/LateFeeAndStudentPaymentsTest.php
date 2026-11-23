<?php

namespace Tests\Feature;

use App\Actions\Payments\ProcessMonthlyFees;
use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Mora configurable por pagar la mensualidad después del día límite, y lo
 * que ve el estudiante: solo sus meses pagados o pendientes, no el estado
 * de cuenta completo.
 */
class LateFeeAndStudentPaymentsTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->adminUser();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function runOn(string $date): void
    {
        Carbon::setTestNow($date.' 06:00');
        app(ProcessMonthlyFees::class)->handle();
    }

    private function activeEnrollment(?Student $student = null): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => $student?->id ?? Student::factory(),
            'level_id' => Level::factory()->create(['monthly_fee' => 200000])->id,
            'status' => 'activa',
            'start_date' => '2026-09-01',
        ]);
    }

    public function test_overdue_monthly_fee_gets_the_configured_late_fee_once(): void
    {
        InstitutionSetting::current()->update(['late_fee_percentage' => 5]);
        $enrollment = $this->activeEnrollment();

        $this->runOn('2026-10-01');
        $payment = Payment::query()->where('enrollment_id', $enrollment->id)->sole();
        $this->runOn('2026-10-05');
        $this->assertEquals(0, $payment->fresh()->late_fee_amount);
        $this->assertEquals(200000, $payment->fresh()->final_amount);

        $this->runOn('2026-10-06');
        $this->runOn('2026-10-07');

        $payment->refresh();
        $this->assertSame('vencido', $payment->status);
        $this->assertEquals(10000, $payment->late_fee_amount);
        $this->assertEquals(210000, $payment->final_amount);
        $this->assertEquals(200000, $payment->base_amount);
    }

    public function test_fee_paid_on_time_or_without_a_percentage_has_no_late_fee(): void
    {
        $paidOnTime = $this->activeEnrollment();
        $this->runOn('2026-10-01');
        Payment::query()->where('enrollment_id', $paidOnTime->id)->sole()->update(['status' => 'pagado', 'paid_at' => '2026-10-04']);

        $noPercentage = $this->activeEnrollment();
        Payment::factory()->create([
            'student_id' => $noPercentage->student_id,
            'enrollment_id' => $noPercentage->id,
            'type' => 'mensualidad',
            'period' => '2026-10',
            'due_date' => '2026-10-05',
            'final_amount' => 200000,
        ]);

        $this->runOn('2026-10-06');

        $this->assertEquals(0, Payment::query()->where('enrollment_id', $paidOnTime->id)->sole()->late_fee_amount);
        $overdue = Payment::query()->where('enrollment_id', $noPercentage->id)->sole();
        $this->assertSame('vencido', $overdue->status);
        $this->assertEquals(0, $overdue->late_fee_amount);
        $this->assertEquals(200000, $overdue->final_amount);
    }

    public function test_enrollment_fee_never_gets_a_late_fee(): void
    {
        InstitutionSetting::current()->update(['late_fee_percentage' => 5]);
        $enrollment = $this->activeEnrollment();
        $payment = Payment::factory()->create([
            'student_id' => $enrollment->student_id,
            'enrollment_id' => $enrollment->id,
            'type' => 'matricula',
            'due_date' => '2026-10-05',
            'final_amount' => 300000,
        ]);

        $this->runOn('2026-10-06');

        $this->assertSame('vencido', $payment->fresh()->status);
        $this->assertEquals(300000, $payment->fresh()->final_amount);
    }

    public function test_admin_configures_the_late_fee_percentage(): void
    {
        $settings = InstitutionSetting::current();

        $this->actingAs($this->adminUser())
            ->post(route('institution-settings.update'), [
                'name' => $settings->name,
                'payment_due_day' => 5,
                'payment_reminder_days' => 2,
                'overdue_alert_days' => 10,
                'late_fee_percentage' => 7.5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(7.5, $settings->fresh()->late_fee_percentage);

        $this->actingAs($this->adminUser())
            ->post(route('institution-settings.update'), ['name' => $settings->name, 'late_fee_percentage' => 150])
            ->assertSessionHasErrors('late_fee_percentage');
    }

    public function test_student_sees_only_their_months_and_pending_payments_not_the_account_statement(): void
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = $this->activeEnrollment($student);
        $paid = Payment::factory()->create(['student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'type' => 'mensualidad', 'period' => '2026-09', 'status' => 'pagado', 'paid_at' => '2026-09-03']);
        $pending = Payment::factory()->create(['student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'type' => 'mensualidad', 'period' => '2026-10', 'status' => 'pendiente']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('studentData.account')
                ->where('studentData.payment_status', 'pendiente')
                ->has('studentData.monthly_fees', 2)
                ->where('studentData.monthly_fees.0.id', $pending->id)
                ->where('studentData.monthly_fees.0.period', '2026-10')
                ->where('studentData.monthly_fees.0.status', 'pendiente')
                ->where('studentData.monthly_fees.1.id', $paid->id)
                ->where('studentData.monthly_fees.1.status', 'pagado')
                ->where('studentData.pending_payments.0.id', $pending->id)
            );

        $this->actingAs($user)->get(route('students.account-statement', $student))->assertForbidden();
    }

    public function test_student_up_to_date_is_shown_as_al_dia(): void
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = $this->activeEnrollment($student);
        Payment::factory()->create(['student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'type' => 'mensualidad', 'period' => '2026-10', 'status' => 'pagado']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('studentData.payment_status', 'al_dia'));
    }
}
