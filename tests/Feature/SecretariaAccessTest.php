<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 3 del plan de mejoras: la secretaria registra y edita estudiantes y
 * matrículas, consulta pagos sin poder registrarlos, y no tiene acceso a
 * la configuración académica ni a la del sistema.
 */
class SecretariaAccessTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_secretaria_is_sent_to_the_students_list_after_login(): void
    {
        $this->actingAs($this->secretariaUser())
            ->get('/dashboard')
            ->assertRedirect(route('students.index'));
    }

    public function test_secretaria_can_create_and_edit_students(): void
    {
        $secretaria = $this->secretariaUser();

        $this->actingAs($secretaria)->get(route('students.index'))->assertOk();
        $this->actingAs($secretaria)->get(route('students.create'))->assertOk();

        $this->actingAs($secretaria)
            ->post(route('students.store'), [
                'code' => 'EST-500',
                'name' => 'Pedro Pérez',
                'email' => 'pedro@instituto.test',
                'status' => 'activo',
            ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHasNoErrors();

        $student = Student::query()->where('code', 'EST-500')->firstOrFail();

        $this->actingAs($secretaria)->get(route('students.edit', $student))->assertOk();
        $this->actingAs($secretaria)
            ->put(route('students.update', $student), [
                'code' => 'EST-500',
                'name' => 'Pedro Pérez Gómez',
                'email' => 'pedro@instituto.test',
                'status' => 'activo',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Pedro Pérez Gómez', $student->fresh()->name);
    }

    public function test_secretaria_cannot_delete_or_import_students_or_grant_portal_access(): void
    {
        $secretaria = $this->secretariaUser();
        $student = Student::factory()->create();

        $this->actingAs($secretaria)->delete(route('students.destroy', $student))->assertForbidden();
        $this->actingAs($secretaria)->get(route('students.import.create'))->assertForbidden();
        $this->actingAs($secretaria)->post(route('students.portal-access.store', $student))->assertForbidden();

        $this->assertModelExists($student);
    }

    public function test_secretaria_can_manage_enrollments(): void
    {
        $secretaria = $this->secretariaUser();
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($secretaria)->get(route('enrollments.index'))->assertOk();
        $this->actingAs($secretaria)->get(route('enrollments.create'))->assertOk();
        $this->actingAs($secretaria)->get(route('enrollments.edit', $enrollment))->assertOk();
    }

    public function test_secretaria_can_view_but_not_register_or_edit_payments(): void
    {
        $secretaria = $this->secretariaUser();
        $payment = Payment::factory()->create();

        $this->actingAs($secretaria)->get(route('payments.index'))->assertOk();
        $this->actingAs($secretaria)->get(route('students.account-statement', $payment->student_id))->assertOk();

        $this->actingAs($secretaria)->get(route('payments.create'))->assertForbidden();
        $this->actingAs($secretaria)->get(route('payments.edit', $payment))->assertForbidden();
        $this->actingAs($secretaria)->post(route('payments.store'), [])->assertForbidden();
    }

    public function test_secretaria_has_no_access_to_other_areas(): void
    {
        $secretaria = $this->secretariaUser();

        foreach ([
            'teachers.index',
            'courses.index',
            'class-sessions.index',
            'attendance.index',
            'promotions.index',
            'reports.index',
            'staff-users.index',
            'password-resets.index',
            'audit-logs.index',
            'institution-settings.edit',
        ] as $routeName) {
            $this->actingAs($secretaria)->get(route($routeName))->assertForbidden();
        }
    }

    public function test_admin_can_create_a_secretaria_account(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('staff-users.store'), [
                'name' => 'Marta Secretaria',
                'email' => 'secretaria@instituto.test',
                'role' => 'secretaria',
                'password' => 'ClaveSegura123',
                'password_confirmation' => 'ClaveSegura123',
            ])
            ->assertRedirect(route('staff-users.index'))
            ->assertSessionHasNoErrors();
    }
}
