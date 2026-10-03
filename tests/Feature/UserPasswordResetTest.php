<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 2 del plan de mejoras: no hay "¿Olvidaste tu contraseña?". El
 * personal restablece la contraseña con una temporal, que la persona debe
 * cambiar al iniciar sesión, y queda registrado en la auditoría.
 */
class UserPasswordResetTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_forgot_password_routes_no_longer_exist(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'alguien@instituto.test'])->assertNotFound();
        $this->get('/reset-password/token')->assertNotFound();
        $this->post('/reset-password')->assertNotFound();
    }

    public function test_admin_resets_a_password_and_the_user_must_change_it_on_login(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentUser();

        $response = $this->actingAs($admin)
            ->post(route('password-resets.store', $student))
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertTrue($student->must_change_password);
        $this->assertFalse(Hash::check('password', $student->password));
        $this->assertDatabaseHas(AuditLog::class, [
            'user_id' => $admin->id,
            'action' => 'contraseña_restablecida',
            'auditable_type' => User::class,
            'auditable_id' => $student->id,
        ]);

        preg_match('/Contraseña temporal: (\S+)\./', $response->getSession()->get('success'), $matches);
        $temporaryPassword = $matches[1];

        auth()->logout();

        $this->post('/login', ['email' => $student->email, 'password' => $temporaryPassword]);
        $this->assertAuthenticatedAs($student);

        $this->get('/dashboard')->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk();

        $this->put(route('password.change.update'), [
            'password' => $temporaryPassword,
            'password_confirmation' => $temporaryPassword,
        ])->assertSessionHasErrors('password');

        $this->put(route('password.change.update'), [
            'password' => 'MiClaveNueva123',
            'password_confirmation' => 'MiClaveNueva123',
        ])->assertRedirect(route('dashboard'));

        $student->refresh();
        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('MiClaveNueva123', $student->password));
        $this->get('/dashboard')->assertOk();
    }

    public function test_admin_can_reset_staff_and_other_admin_accounts(): void
    {
        $admin = $this->adminUser();

        foreach ([$this->cajeroUser(), $this->adminUser()] as $user) {
            $this->actingAs($admin)
                ->post(route('password-resets.store', $user))
                ->assertSessionHasNoErrors();

            $this->assertTrue($user->fresh()->must_change_password);
        }
    }

    public function test_coordinador_can_reset_students_and_teachers_only(): void
    {
        $coordinador = $this->coordinadorUser();

        foreach ([$this->studentUser(), $this->teacherUser()] as $user) {
            $this->actingAs($coordinador)
                ->post(route('password-resets.store', $user))
                ->assertSessionHasNoErrors();
        }

        foreach ([$this->cajeroUser(), $this->adminUser()] as $user) {
            $this->actingAs($coordinador)
                ->post(route('password-resets.store', $user))
                ->assertForbidden();

            $this->assertFalse($user->fresh()->must_change_password);
        }
    }

    public function test_coordinador_only_sees_students_and_teachers_in_the_list(): void
    {
        $coordinador = $this->coordinadorUser();
        $student = $this->studentUser();
        $cashier = $this->cajeroUser();

        $this->actingAs($coordinador)
            ->get(route('password-resets.index'))
            ->assertOk()
            ->assertSee($student->email)
            ->assertDontSee($cashier->email);
    }

    public function test_nobody_can_reset_their_own_password_from_this_screen(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('password-resets.store', $admin))
            ->assertForbidden();
    }

    public function test_other_roles_cannot_reset_passwords(): void
    {
        $student = $this->studentUser();

        foreach ([$this->cajeroUser(), $this->secretariaUser(), $this->teacherUser()] as $user) {
            $this->actingAs($user)->get(route('password-resets.index'))->assertForbidden();
            $this->actingAs($user)->post(route('password-resets.store', $student))->assertForbidden();
        }
    }
}
