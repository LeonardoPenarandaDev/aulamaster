<?php

namespace Tests\Feature;

use App\Actions\Attendance\RecalculateEnrollmentHours;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Reglas 2, 3 y 13 de la sección 40: la asistencia debe estar vinculada a
 * una clase concreta, las horas se acumulan solo con asistencias válidas
 * ("presente"), y una asistencia confirmada no puede modificarse ni
 * eliminarse directamente.
 */
class AttendanceRulesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_attendance_is_tied_to_a_specific_class_session(): void
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(ClassSession::class, $attendance->classSession);
    }

    public function test_only_present_attendance_counts_toward_accumulated_hours(): void
    {
        $enrollment = Enrollment::factory()->create(['required_hours' => 128, 'accumulated_hours' => 0]);
        $teacher = Teacher::factory()->create();
        $registeredBy = User::factory()->create();

        // Sección 17 del plan: 3 clases de 2 horas presente + 1 ausente = 6 horas.
        foreach (['presente', 'presente', 'ausente', 'presente'] as $status) {
            $session = ClassSession::factory()->create([
                'level_id' => $enrollment->level_id,
                'start_time' => '14:00',
                'end_time' => '16:00',
            ]);

            Attendance::factory()->create([
                'class_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $teacher->id,
                'status' => $status,
                'registered_by_id' => $registeredBy->id,
            ]);
        }

        app(RecalculateEnrollmentHours::class)->handle($enrollment);

        $this->assertEquals(6, $enrollment->fresh()->accumulated_hours);
    }

    public function test_confirmed_attendance_has_no_update_or_delete_route(): void
    {
        $this->assertFalse(Route::has('attendance.update'));
        $this->assertFalse(Route::has('attendance.destroy'));

        // No existe ninguna ruta (de ningún verbo) para editar/borrar un registro puntual.
        $attendance = Attendance::factory()->create();
        $admin = $this->adminUser();

        $this->actingAs($admin)->put("/attendance/{$attendance->id}")->assertNotFound();
        $this->actingAs($admin)->delete("/attendance/{$attendance->id}")->assertNotFound();
    }

    public function test_correction_creates_a_new_record_and_keeps_the_original_untouched(): void
    {
        $attendance = Attendance::factory()->create(['status' => 'ausente']);
        $corrector = User::factory()->create();

        $attendance->corrections()->create([
            'previous_status' => 'ausente',
            'new_status' => 'presente',
            'reason' => 'Corrección de prueba',
            'corrected_by_id' => $corrector->id,
        ]);

        $attendance->refresh();

        $this->assertSame('ausente', $attendance->status);
        $this->assertSame('presente', $attendance->effective_status);
        $this->assertCount(1, $attendance->corrections);
    }

    public function test_teacher_can_only_register_attendance_for_students_enrolled_in_the_session_level(): void
    {
        $user = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $session = ClassSession::factory()->create(['teacher_id' => $teacher->id]);

        $sameLevel = Enrollment::factory()->create(['level_id' => $session->level_id]);
        $otherLevel = Enrollment::factory()->create();
        $cancelled = Enrollment::factory()->create(['level_id' => $session->level_id, 'status' => 'cancelada']);

        $this->actingAs($user)
            ->post(route('attendance.store', $session), ['records' => [
                ['enrollment_id' => $otherLevel->id, 'status' => 'presente'],
            ]])
            ->assertSessionHasErrors('records.0.enrollment_id');

        $this->actingAs($user)
            ->post(route('attendance.store', $session), ['records' => [
                ['enrollment_id' => $cancelled->id, 'status' => 'presente'],
            ]])
            ->assertSessionHasErrors('records.0.enrollment_id');

        $this->actingAs($user)
            ->post(route('attendance.store', $session), ['records' => [
                ['enrollment_id' => $sameLevel->id, 'status' => 'presente'],
            ]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['class_session_id' => $session->id, 'enrollment_id' => $sameLevel->id]);
    }
}
