<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Fase 15 del checklist: los roles `coordinador` y `cajero` tienen acceso de
 * alcance único (uno solo al calendario académico, el otro solo a
 * matrículas/pagos), y un profesor solo puede tomar asistencia el mismo día
 * de la clase.
 */
class RoleAccessTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_coordinador_can_manage_schedule_but_not_students_or_payments(): void
    {
        $coordinador = $this->coordinadorUser();

        $this->actingAs($coordinador)->get('/class-sessions')->assertOk();
        $this->actingAs($coordinador)->get('/courses')->assertOk();

        $this->actingAs($coordinador)->get('/students')->assertForbidden();
        $this->actingAs($coordinador)->get('/enrollments')->assertForbidden();
        $this->actingAs($coordinador)->get('/payments')->assertForbidden();
    }

    public function test_cajero_can_manage_enrollments_and_payments_but_not_the_academic_setup(): void
    {
        $cajero = $this->cajeroUser();

        $this->actingAs($cajero)->get('/enrollments')->assertOk();
        $this->actingAs($cajero)->get('/payments')->assertOk();
        $this->actingAs($cajero)->get('/promotions')->assertOk();

        $this->actingAs($cajero)->get('/courses')->assertForbidden();
        $this->actingAs($cajero)->get('/students')->assertForbidden();
        $this->actingAs($cajero)->get('/attendance')->assertForbidden();
    }

    public function test_teacher_can_only_take_attendance_on_the_class_day(): void
    {
        $teacherUser = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $today = ClassSession::factory()->create(['teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        $yesterday = ClassSession::factory()->create(['teacher_id' => $teacher->id, 'date' => now()->subDay()->toDateString()]);
        $tomorrow = ClassSession::factory()->create(['teacher_id' => $teacher->id, 'date' => now()->addDay()->toDateString()]);

        $this->actingAs($teacherUser)->get("/class-sessions/{$today->id}/attendance")->assertOk();
        $this->actingAs($teacherUser)->get("/class-sessions/{$yesterday->id}/attendance")->assertForbidden();
        $this->actingAs($teacherUser)->get("/class-sessions/{$tomorrow->id}/attendance")->assertForbidden();
    }

    public function test_admin_bypasses_the_attendance_date_restriction(): void
    {
        $admin = $this->adminUser();
        $teacher = Teacher::factory()->create();
        $yesterday = ClassSession::factory()->create(['teacher_id' => $teacher->id, 'date' => now()->subDay()->toDateString()]);

        $this->actingAs($admin)->get("/class-sessions/{$yesterday->id}/attendance")->assertOk();
    }

    /**
     * El cajero solo debe ver los reportes financieros (ingresos, pagos
     * pendientes, promociones usadas, referidos, recuperaciones pagadas),
     * no los académicos/de asistencia/auditoría.
     */
    public function test_cajero_only_sees_financial_reports(): void
    {
        $cajero = $this->cajeroUser();

        foreach (['income', 'students-pending-payments', 'promotions-used', 'referrals', 'recoveries-paid'] as $key) {
            $this->actingAs($cajero)->get("/reports/{$key}")->assertOk();
        }

        foreach (['students', 'attendance-by-date', 'evaluations-approved', 'audit', 'schedules'] as $key) {
            $this->actingAs($cajero)->get("/reports/{$key}")->assertForbidden();
        }
    }

    public function test_admin_sees_every_report(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/reports/students')->assertOk();
        $this->actingAs($admin)->get('/reports/audit')->assertOk();
    }
}
