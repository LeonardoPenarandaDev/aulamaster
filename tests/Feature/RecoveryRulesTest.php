<?php

namespace Tests\Feature;

use App\Actions\Recovery\DetermineRecoveryTerms;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\Extension;
use App\Models\Payment;
use App\Models\RecoverySetting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reglas 6, 7, 9 y 10 de la sección 40: la primera recuperación es
 * gratuita, hasta dos recuperaciones pagadas por evaluación, el nivel puede
 * extenderse durante una recuperación, y una recuperación pagada debe
 * quedar vinculada a su pago.
 */
class RecoveryRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_recovery_attempt_is_free_and_subsequent_ones_are_paid(): void
    {
        RecoverySetting::current()->update(['free_attempts' => 1, 'max_paid_attempts' => 2, 'recovery_price' => 50000]);

        $enrollment = Enrollment::factory()->create();
        $teacher = Teacher::factory()->create();
        $registeredBy = User::factory()->create();
        $evaluation = Evaluation::factory()->create(['level_id' => $enrollment->level_id, 'minimum_grade' => 70]);

        $costsByAttempt = [];

        // Sección 23 del plan: intento original + 3 recuperaciones, todas reprobadas.
        for ($i = 0; $i < 4; $i++) {
            $terms = app(DetermineRecoveryTerms::class)->handle($evaluation, $enrollment);
            $costsByAttempt[] = $terms['cost'];

            EvaluationResult::factory()->create([
                'evaluation_id' => $evaluation->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $teacher->id,
                'attempt_number' => $terms['attempt_number'],
                'is_recovery' => $terms['is_recovery'],
                'cost' => $terms['cost'],
                'grade' => 50,
                'result' => 'reprobado',
                'registered_by_id' => $registeredBy->id,
            ]);
        }

        // Intento #1 (original) y recuperación #1 (gratuita): sin costo.
        $this->assertEquals([0, 0, 50000, 50000], $costsByAttempt);
    }

    public function test_recovery_attempts_are_blocked_after_the_maximum_configured(): void
    {
        RecoverySetting::current()->update(['free_attempts' => 1, 'max_paid_attempts' => 2]);

        $enrollment = Enrollment::factory()->create();
        $teacher = Teacher::factory()->create();
        $registeredBy = User::factory()->create();
        $evaluation = Evaluation::factory()->create(['level_id' => $enrollment->level_id, 'minimum_grade' => 70]);

        for ($i = 0; $i < 4; $i++) {
            $terms = app(DetermineRecoveryTerms::class)->handle($evaluation, $enrollment);

            EvaluationResult::factory()->create([
                'evaluation_id' => $evaluation->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $teacher->id,
                'attempt_number' => $terms['attempt_number'],
                'is_recovery' => $terms['is_recovery'],
                'cost' => $terms['cost'],
                'grade' => 50,
                'result' => 'reprobado',
                'registered_by_id' => $registeredBy->id,
            ]);
        }

        $terms = app(DetermineRecoveryTerms::class)->handle($evaluation, $enrollment);

        $this->assertTrue($terms['blocked']);
    }

    public function test_paid_recovery_result_is_linked_to_a_payment(): void
    {
        $result = EvaluationResult::factory()->create(['cost' => 50000, 'payment_status' => 'pagado']);
        $result->update(['payment_id' => Payment::factory()->create(['final_amount' => 50000])->id]);

        $this->assertNotNull($result->fresh()->payment);
        $this->assertEquals(50000, $result->fresh()->payment->final_amount);
    }

    public function test_level_can_be_extended_during_recovery_with_a_recorded_reason(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => 'en_recuperacion',
            'estimated_end_date' => '2026-12-01',
        ]);
        $admin = User::factory()->create();

        $enrollment->extensions()->create([
            'previous_end_date' => $enrollment->estimated_end_date,
            'new_end_date' => '2027-01-15',
            'reason' => 'Recuperación de evaluación',
            'extended_by_id' => $admin->id,
        ]);
        $enrollment->update(['estimated_end_date' => '2027-01-15']);

        $this->assertCount(1, $enrollment->fresh()->extensions);
        $this->assertInstanceOf(Extension::class, $enrollment->fresh()->extensions->first());
        $this->assertSame('2027-01-15', $enrollment->fresh()->estimated_end_date->toDateString());
    }
}
