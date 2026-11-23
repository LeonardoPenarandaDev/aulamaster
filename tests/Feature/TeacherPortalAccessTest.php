<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Al registrar un profesor se le crea el acceso a la app: el usuario es su
 * correo y la contraseña inicial su número de documento. El admin puede
 * cambiarle la contraseña después.
 */
class TeacherPortalAccessTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function teacherData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'DOC-900',
            'name' => 'Carlos Ruiz',
            'document' => '1032456789',
            'email' => 'carlos@instituto.test',
            'status' => 'activo',
            'portal_access' => true,
        ], $overrides);
    }

    private function teacherWithAccess(): Teacher
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData())
            ->assertSessionHasNoErrors();

        return Teacher::query()->where('code', 'DOC-900')->firstOrFail();
    }

    public function test_creating_a_teacher_uses_email_as_user_and_document_as_password(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData())
            ->assertRedirect(route('teachers.index'))
            ->assertSessionHasNoErrors();

        $teacher = Teacher::query()->where('code', 'DOC-900')->firstOrFail();
        $user = User::query()->where('email', 'carlos@instituto.test')->firstOrFail();

        $this->assertSame($user->id, $teacher->user_id);
        $this->assertTrue($user->hasRole('profesor'));
        $this->assertTrue(Hash::check('1032456789', $user->password));
    }

    public function test_teacher_can_be_created_without_portal_access(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData(['portal_access' => false, 'document' => '']))
            ->assertRedirect(route('teachers.index'));

        $this->assertNull(Teacher::query()->where('code', 'DOC-900')->value('user_id'));
        $this->assertDatabaseMissing('users', ['email' => 'carlos@instituto.test']);
    }

    public function test_portal_access_requires_email_and_document(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('teachers.store'), $this->teacherData(['email' => '']))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('teachers.store'), $this->teacherData(['document' => '']))
            ->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('teachers', ['code' => 'DOC-900']);
    }

    public function test_portal_access_cannot_reuse_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'carlos@instituto.test']);

        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('teachers', ['code' => 'DOC-900']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updateData(array $overrides = []): array
    {
        return $this->teacherData(array_merge(['portal_access' => null], $overrides));
    }

    public function test_admin_can_change_the_teacher_password_when_editing(): void
    {
        $teacher = $this->teacherWithAccess();

        $this->put(route('teachers.update', $teacher), $this->updateData([
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ]))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NuevaClave2026', $teacher->user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contraseña_cambiada',
            'auditable_type' => User::class,
            'auditable_id' => $teacher->user_id,
        ]);
    }

    public function test_changing_the_password_requires_confirmation(): void
    {
        $teacher = $this->teacherWithAccess();

        $this->put(route('teachers.update', $teacher), $this->updateData([
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'OtraClave2026',
        ]))->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('1032456789', $teacher->user->fresh()->password));
    }

    public function test_only_admin_can_change_a_teacher_password(): void
    {
        $teacher = $this->teacherWithAccess();

        $this->actingAs($this->teacherUser())
            ->put(route('teachers.update', $teacher), $this->updateData([
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ]))
            ->assertForbidden();

        $this->assertTrue(Hash::check('1032456789', $teacher->user->fresh()->password));
    }

    public function test_updating_the_teacher_email_updates_the_login_user(): void
    {
        $teacher = $this->teacherWithAccess();

        $this->put(route('teachers.update', $teacher), [
            'code' => 'DOC-900',
            'name' => 'Carlos A. Ruiz',
            'document' => '1032456789',
            'email' => 'carlos.ruiz@instituto.test',
            'status' => 'activo',
        ])->assertSessionHasNoErrors();

        $this->assertSame('carlos.ruiz@instituto.test', $teacher->user->fresh()->email);
        $this->assertSame('Carlos A. Ruiz', $teacher->user->fresh()->name);
    }

    public function test_editing_without_password_keeps_the_current_one(): void
    {
        $teacher = $this->teacherWithAccess();

        $this->put(route('teachers.update', $teacher), $this->updateData(['phone' => '3001234567']))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('1032456789', $teacher->user->fresh()->password));
    }

    public function test_setting_a_password_when_editing_creates_the_account(): void
    {
        $teacher = Teacher::factory()->create(['email' => 'nuevo@instituto.test']);

        $this->actingAs($this->adminUser())
            ->put(route('teachers.update', $teacher), [
                'code' => $teacher->code,
                'name' => $teacher->name,
                'email' => 'nuevo@instituto.test',
                'status' => 'activo',
                'password' => 'ClaveInicial1',
                'password_confirmation' => 'ClaveInicial1',
            ])
            ->assertSessionHasNoErrors();

        $user = $teacher->fresh()->user;
        $this->assertSame('nuevo@instituto.test', $user->email);
        $this->assertTrue($user->hasRole('profesor'));
        $this->assertTrue(Hash::check('ClaveInicial1', $user->password));
    }

    public function test_granting_access_later_uses_the_document_as_password(): void
    {
        $teacher = Teacher::factory()->create(['document' => '987654321']);

        $this->actingAs($this->adminUser())
            ->post(route('teachers.portal-access.store', $teacher))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('987654321', $teacher->fresh()->user->password));
    }
}
