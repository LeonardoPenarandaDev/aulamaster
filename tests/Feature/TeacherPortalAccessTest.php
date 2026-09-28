<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Al registrar un profesor, el admin puede definir su contraseña para
 * crearle el acceso al portal en el mismo paso.
 */
class TeacherPortalAccessTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function teacherData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'DOC-900',
            'name' => 'Carlos Ruiz',
            'email' => 'carlos@instituto.test',
            'status' => 'activo',
        ], $overrides);
    }

    public function test_admin_can_create_a_teacher_with_a_portal_password(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertRedirect(route('teachers.index'))
            ->assertSessionHasNoErrors();

        $teacher = Teacher::query()->where('code', 'DOC-900')->firstOrFail();
        $user = User::query()->where('email', 'carlos@instituto.test')->firstOrFail();

        $this->assertSame($user->id, $teacher->user_id);
        $this->assertTrue($user->hasRole('profesor'));
        $this->assertTrue(Hash::check('ClaveSegura123', $user->password));
    }

    public function test_teacher_can_be_created_without_a_password(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData())
            ->assertRedirect(route('teachers.index'));

        $this->assertNull(Teacher::query()->where('code', 'DOC-900')->value('user_id'));
        $this->assertDatabaseMissing('users', ['email' => 'carlos@instituto.test']);
    }

    public function test_password_requires_email_and_confirmation(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('teachers.store'), $this->teacherData([
                'email' => '',
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('teachers.store'), $this->teacherData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'OtraClave456',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('teachers', ['code' => 'DOC-900']);
    }

    public function test_password_cannot_reuse_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'carlos@instituto.test']);

        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), $this->teacherData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('teachers', ['code' => 'DOC-900']);
    }
}
