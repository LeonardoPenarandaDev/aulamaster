<?php

namespace Tests\Feature;

use App\Actions\Evaluations\CheckEvaluationEligibility;
use App\Actions\Evaluations\EvaluateLevelCompletion;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regla 4 de la sección 40 (una evaluación confirmada no puede modificarse
 * directamente) y la lógica de aprobación de nivel de la sección 21.
 */
class EvaluationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_result_controller_exposes_no_mutating_route_for_a_single_result(): void
    {
        $mutatingMethods = ['PUT', 'PATCH', 'DELETE'];

        foreach (Route::getRoutes() as $route) {
            if ($route->getActionName() === 'App\Http\Controllers\EvaluationResultController@store') {
                continue; // store (crear un nuevo intento) sí es POST, no toca un registro existente.
            }

            if (str_contains($route->getActionName(), 'EvaluationResultController')) {
                $this->assertEmpty(
                    array_intersect($mutatingMethods, $route->methods()),
                    "La ruta {$route->uri()} no debería permitir editar/borrar un resultado confirmado.",
                );
            }
        }
    }

    public function test_student_cannot_present_evaluation_without_completing_hours(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => 'activa',
            'required_hours' => 128,
            'accumulated_hours' => 100,
        ]);

        $missing = app(CheckEvaluationEligibility::class)->handle($enrollment);

        $this->assertNotEmpty($missing);
        $this->assertStringContainsString('28', $missing[0]);
    }

    public function test_level_is_approved_when_all_evaluations_pass_and_hours_are_complete(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => 'activa',
            'required_hours' => 128,
            'accumulated_hours' => 128,
        ]);
        $teacher = Teacher::factory()->create();
        $registeredBy = User::factory()->create();

        foreach (['Listening', 'Speaking', 'Reading', 'Writing'] as $name) {
            $evaluation = Evaluation::factory()->create([
                'level_id' => $enrollment->level_id,
                'name' => $name,
                'minimum_grade' => 70,
                'status' => 'activo',
            ]);

            EvaluationResult::factory()->create([
                'evaluation_id' => $evaluation->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $teacher->id,
                'grade' => 85,
                'result' => 'aprobado',
                'registered_by_id' => $registeredBy->id,
            ]);

            app(EvaluateLevelCompletion::class)->handle($enrollment->fresh());
        }

        $this->assertSame('aprobada', $enrollment->fresh()->status);
        $this->assertNotNull($enrollment->fresh()->actual_end_date);
    }

    public function test_enrollment_enters_recovery_when_an_evaluation_is_failed(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => 'activa',
            'required_hours' => 128,
            'accumulated_hours' => 128,
        ]);
        $teacher = Teacher::factory()->create();
        $registeredBy = User::factory()->create();

        $evaluation = Evaluation::factory()->create([
            'level_id' => $enrollment->level_id,
            'minimum_grade' => 70,
            'status' => 'activo',
        ]);

        EvaluationResult::factory()->create([
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'teacher_id' => $teacher->id,
            'grade' => 55,
            'result' => 'reprobado',
            'registered_by_id' => $registeredBy->id,
        ]);

        app(EvaluateLevelCompletion::class)->handle($enrollment->fresh());

        $this->assertSame('en_recuperacion', $enrollment->fresh()->status);
    }
}
