<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Fase 16 del checklist: el certificado de nivel aprobado se emite bajo
 * pedido (del admin o del propio estudiante, nunca se envía automático),
 * solo para matrículas ya aprobadas, y cada emisión queda registrada en
 * Auditoría. La matrícula se aprueba al tener todas las evaluaciones
 * aprobadas y las horas completas, en cualquier orden.
 */
class CertificateTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_can_download_the_certificate_of_an_approved_enrollment(): void
    {
        InstitutionSetting::current()->update(['name' => 'Instituto de Prueba']);
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $response = $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_certificate_is_blocked_for_an_enrollment_that_is_not_approved(): void
    {
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'activa']);

        $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate")->assertStatus(422);
    }

    public function test_only_admin_can_issue_certificates_or_edit_institution_settings(): void
    {
        $cajero = $this->cajeroUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $this->actingAs($cajero)->get("/enrollments/{$enrollment->id}/certificate")->assertForbidden();
        $this->actingAs($cajero)->get('/institution-settings')->assertForbidden();
    }

    public function test_issuing_a_certificate_is_recorded_in_the_audit_log(): void
    {
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate");

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'certificados',
            'action' => 'emitido',
            'auditable_type' => Enrollment::class,
            'auditable_id' => $enrollment->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_student_can_download_their_own_approved_certificate(): void
    {
        InstitutionSetting::current();
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'status' => 'aprobada']);

        $response = $this->actingAs($user)->get(route('student-certificates.download', $enrollment));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_student_cannot_download_another_students_or_unapproved_certificate(): void
    {
        InstitutionSetting::current();
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $notApproved = Enrollment::factory()->create(['student_id' => $student->id, 'status' => 'activa']);
        $someoneElses = Enrollment::factory()->create(['status' => 'aprobada']);

        $this->actingAs($user)->get(route('student-certificates.download', $notApproved))->assertStatus(422);
        $this->actingAs($user)->get(route('student-certificates.download', $someoneElses))->assertForbidden();
    }

    public function test_student_dashboard_lists_approved_levels_for_download(): void
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'status' => 'aprobada', 'actual_end_date' => '2026-09-20']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('studentData.certificates', 1)
                ->where('studentData.certificates.0.enrollment_id', $enrollment->id)
            );
    }

    public function test_completing_the_hours_after_passing_the_exams_approves_the_enrollment(): void
    {
        $teacherUser = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $enrollment = Enrollment::factory()->create(['required_hours' => 4, 'accumulated_hours' => 2]);
        $evaluation = Evaluation::factory()->create(['level_id' => $enrollment->level_id]);
        EvaluationResult::factory()->create([
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'grade' => 90,
            'result' => 'aprobado',
        ]);

        $earlier = ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'date' => today()->subDay(), 'start_time' => '14:00', 'end_time' => '16:00']);
        Attendance::factory()->create(['class_session_id' => $earlier->id, 'enrollment_id' => $enrollment->id, 'status' => 'presente']);

        $today = ClassSession::factory()->create([
            'level_id' => $enrollment->level_id,
            'teacher_id' => $teacher->id,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]);

        $this->actingAs($teacherUser)
            ->post(route('attendance.store', $today), ['records' => [
                ['enrollment_id' => $enrollment->id, 'status' => 'presente'],
            ]])
            ->assertSessionHasNoErrors();

        $enrollment->refresh();
        $this->assertEquals(4, $enrollment->accumulated_hours);
        $this->assertSame('aprobada', $enrollment->status);
    }

    public function test_completing_the_hours_without_passing_the_exams_does_not_approve(): void
    {
        $teacherUser = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $enrollment = Enrollment::factory()->create(['required_hours' => 2]);
        Evaluation::factory()->create(['level_id' => $enrollment->level_id]);

        $today = ClassSession::factory()->create([
            'level_id' => $enrollment->level_id,
            'teacher_id' => $teacher->id,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]);

        $this->actingAs($teacherUser)
            ->post(route('attendance.store', $today), ['records' => [
                ['enrollment_id' => $enrollment->id, 'status' => 'presente'],
            ]]);

        $this->assertSame('activa', $enrollment->fresh()->status);
    }
}
