<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El administrador entra a la cuenta de un estudiante sin su contraseña y
 * vuelve a la suya.
 */
class ImpersonationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function studentWithAccount(): Student
    {
        $user = $this->studentUser();

        return Student::factory()->create(['user_id' => $user->id, 'name' => 'Ana Gómez']);
    }

    public function test_admin_enters_the_student_account_and_goes_back(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();

        $this->actingAs($admin)
            ->post(route('students.impersonate', $student))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student->user);
        $this->assertSame($admin->id, session('impersonator_id'));
        $this->assertDatabaseHas(AuditLog::class, [
            'user_id' => $admin->id,
            'action' => 'ingreso_como_estudiante',
            'auditable_type' => User::class,
            'auditable_id' => $student->user_id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('impersonating.student', $student->user->name));

        $this->get(route('student-schedule.index'))->assertOk();
        $this->get(route('students.index'))->assertForbidden();

        $this->post(route('impersonation.leave'))->assertRedirect(route('students.edit', $student));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
        $this->get(route('dashboard'))->assertInertia(fn (AssertableInertia $page) => $page->where('impersonating', null));
    }

    public function test_student_with_overdue_payments_can_still_go_back_to_the_admin(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();
        Payment::factory()->create(['student_id' => $student->id, 'status' => 'vencido', 'type' => 'mensualidad']);

        $this->actingAs($admin)->post(route('students.impersonate', $student));
        $this->get(route('student-schedule.index'))->assertRedirect(route('student-account.blocked'));
        $this->post(route('impersonation.leave'))->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_only_admin_can_enter_a_student_account(): void
    {
        $student = $this->studentWithAccount();

        foreach ([$this->secretariaUser(), $this->cajeroUser(), $this->teacherUser(), $this->studentUser()] as $user) {
            $this->actingAs($user)->post(route('students.impersonate', $student))->assertForbidden();
            $this->assertAuthenticatedAs($user);
        }
    }

    public function test_student_without_portal_access_cannot_be_entered(): void
    {
        $student = Student::factory()->create();
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('students.impersonate', $student))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_leaving_without_impersonating_is_forbidden(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)->post(route('impersonation.leave'))->assertForbidden();
        $this->assertAuthenticatedAs($student);
    }

    public function test_student_edit_page_offers_the_button_only_to_admin(): void
    {
        $student = $this->studentWithAccount();

        $this->actingAs($this->adminUser())
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canImpersonate', true));

        $this->actingAs($this->secretariaUser())
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canImpersonate', false));
    }
}
