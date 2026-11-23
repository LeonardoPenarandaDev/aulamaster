<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El instituto administra las credenciales de los estudiantes: no cambian
 * su contraseña ni eliminan su cuenta (ni siquiera la temporal al entrar).
 */
class StudentCredentialsTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_student_profile_hides_the_password_and_delete_account_forms(): void
    {
        $this->actingAs($this->studentUser())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('managesOwnCredentials', false));

        $this->actingAs($this->teacherUser())
            ->get(route('profile.edit'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('managesOwnCredentials', true));
    }

    public function test_student_cannot_change_their_password(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'OtraClave123!',
                'password_confirmation' => 'OtraClave123!',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $student->fresh()->password));
    }

    public function test_student_cannot_delete_their_account(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertForbidden();

        $this->assertModelExists($student);
        $this->assertAuthenticatedAs($student);
    }

    public function test_student_is_not_forced_to_change_a_temporary_password(): void
    {
        $student = $this->studentUser();
        $student->forceFill(['must_change_password' => true])->save();

        $this->actingAs($student)->get(route('dashboard'))->assertOk();
        $this->actingAs($student)->get(route('password.change'))->assertRedirect(route('dashboard'));
        $this->actingAs($student)
            ->put(route('password.change.update'), ['password' => 'OtraClave123!', 'password_confirmation' => 'OtraClave123!'])
            ->assertForbidden();
    }

    public function test_reset_and_new_portal_access_do_not_ask_students_to_change_the_password(): void
    {
        $admin = $this->adminUser();
        $studentUser = $this->studentUser();

        $this->actingAs($admin)->post(route('password-resets.store', $studentUser))->assertSessionHasNoErrors();
        $this->assertFalse($studentUser->fresh()->must_change_password);

        $student = Student::factory()->create(['email' => 'nuevo@alumno.test']);
        $this->actingAs($admin)->post(route('students.portal-access.store', $student))->assertSessionHasNoErrors();
        $this->assertFalse(User::where('email', 'nuevo@alumno.test')->sole()->must_change_password);
    }

    public function test_staff_and_teachers_still_manage_their_own_password(): void
    {
        $teacher = $this->teacherUser();

        $this->actingAs($teacher)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'OtraClave123!',
                'password_confirmation' => 'OtraClave123!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('OtraClave123!', $teacher->fresh()->password));
    }
}
