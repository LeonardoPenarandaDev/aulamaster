<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Al registrar un estudiante, el admin puede definir su contraseña para
 * crearle el acceso al portal en el mismo paso.
 */
class StudentPortalAccessTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function studentData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'EST-900',
            'name' => 'Ana Gómez',
            'email' => 'ana@instituto.test',
            'status' => 'activo',
        ], $overrides);
    }

    public function test_admin_can_create_a_student_with_a_portal_password(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('students.store'), $this->studentData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertRedirect(route('students.index'))
            ->assertSessionHasNoErrors();

        $student = Student::query()->where('code', 'EST-900')->firstOrFail();
        $user = User::query()->where('email', 'ana@instituto.test')->firstOrFail();

        $this->assertSame($user->id, $student->user_id);
        $this->assertTrue($user->hasRole('estudiante'));
        $this->assertTrue(Hash::check('ClaveSegura123', $user->password));
    }

    public function test_student_can_be_created_without_a_password(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('students.store'), $this->studentData())
            ->assertRedirect(route('students.index'));

        $this->assertNull(Student::query()->where('code', 'EST-900')->value('user_id'));
        $this->assertDatabaseMissing('users', ['email' => 'ana@instituto.test']);
    }

    public function test_password_requires_email_and_confirmation(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentData([
                'email' => '',
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'OtraClave456',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('students', ['code' => 'EST-900']);
    }

    public function test_password_cannot_reuse_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'ana@instituto.test']);

        $this->actingAs($this->adminUser())
            ->post(route('students.store'), $this->studentData([
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('students', ['code' => 'EST-900']);
    }
}
