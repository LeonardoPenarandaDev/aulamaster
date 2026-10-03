<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 9 del plan de mejoras: alumnos y docentes tienen su propio inicio
 * en el portal; el personal administrativo mantiene el panel actual.
 */
class PortalDashboardTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_student_and_teacher_get_their_portal_home(): void
    {
        $studentUser = $this->studentUser();
        Student::factory()->create(['user_id' => $studentUser->id]);
        $teacherUser = $this->teacherUser();
        Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $this->actingAs($studentUser)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Portal/StudentDashboard')->has('studentData'));

        $this->actingAs($teacherUser)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Portal/TeacherDashboard')->has('teacherData.today_sessions'));
    }

    public function test_admin_keeps_the_administrative_dashboard(): void
    {
        $this->actingAs($this->adminUser())->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard')->has('stats'));
    }
}
