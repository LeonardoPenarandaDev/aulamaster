<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El admin gestiona las cuentas del personal (admin, coordinador, cajero)
 * sin usar la consola. Las cuentas no se eliminan: se desactivan.
 */
class StaffUserTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_can_create_a_cashier_who_can_then_log_in(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('staff-users.store'), [
                'name' => 'Laura Caja',
                'email' => 'caja@instituto.test',
                'role' => 'cajero',
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ])
            ->assertRedirect(route('staff-users.index'))
            ->assertSessionHasNoErrors();

        $cashier = User::query()->where('email', 'caja@instituto.test')->firstOrFail();
        $this->assertTrue($cashier->hasRole('cajero'));
        $this->assertTrue($cashier->is_active);
        $this->assertNotNull($cashier->email_verified_at);

        auth()->logout();

        $this->post('/login', ['email' => 'caja@instituto.test', 'password' => 'ClaveSegura123'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_admin_can_change_role_and_password(): void
    {
        $cashier = $this->cajeroUser();

        $this->actingAs($this->adminUser())
            ->put(route('staff-users.update', $cashier), [
                'name' => $cashier->name,
                'email' => $cashier->email,
                'role' => 'coordinador',
                'is_active' => true,
                'password' => 'NuevaClave456',
                'password_confirmation' => 'NuevaClave456',
            ])
            ->assertRedirect(route('staff-users.index'))
            ->assertSessionHasNoErrors();

        $cashier->refresh();
        $this->assertSame(['coordinador'], $cashier->getRoleNames()->all());
        $this->assertTrue(Hash::check('NuevaClave456', $cashier->password));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $cashier = $this->cajeroUser();

        $this->actingAs($this->adminUser())
            ->put(route('staff-users.update', $cashier), [
                'name' => $cashier->name,
                'email' => $cashier->email,
                'role' => 'cajero',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->put(route('staff-users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'cajero',
                'is_active' => false,
            ])
            ->assertSessionHasErrors(['role', 'is_active']);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_student_accounts_are_not_listed_or_editable_here(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentUser();

        $this->actingAs($admin)
            ->get(route('staff-users.index'))
            ->assertOk()
            ->assertDontSee($student->email);

        $this->actingAs($admin)
            ->get(route('staff-users.edit', $student))
            ->assertForbidden();
    }

    public function test_cashier_cannot_manage_staff_users(): void
    {
        $this->actingAs($this->cajeroUser())
            ->get(route('staff-users.index'))
            ->assertForbidden();
    }
}
