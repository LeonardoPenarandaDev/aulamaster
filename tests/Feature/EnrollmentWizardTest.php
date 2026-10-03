<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithContracts;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 6.3 del plan de mejoras: el asistente crea el estudiante (si es
 * nuevo), la matrícula pendiente y sus contratos, y sigue con la firma.
 */
class EnrollmentWizardTest extends TestCase
{
    use InteractsWithContracts, InteractsWithRoles, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function wizardData(Level $level, array $overrides = []): array
    {
        return array_merge([
            'level_id' => $level->id,
            'enrolled_at' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'required_hours' => 100,
            'weekly_hours' => 6,
            'base_price' => 1000000,
            'sign_method' => 'oficina',
        ], $overrides);
    }

    public function test_secretaria_enrolls_a_new_minor_and_goes_to_sign_in_the_office(): void
    {
        $level = Level::factory()->create();
        $this->publishedTemplate(['name' => 'Contrato de matrícula']);

        $response = $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.wizard.store'), $this->wizardData($level, [
                'new_student' => [
                    'code' => 'EST-900',
                    'name' => 'Luis Pérez',
                    'document_type' => 'TI',
                    'birth_date' => now()->subYears(12)->toDateString(),
                    'guardian_name' => 'Marta Pérez',
                    'guardian_email' => 'marta@correo.test',
                ],
                'special_clauses' => [],
            ]));

        $student = Student::query()->where('code', 'EST-900')->firstOrFail();
        $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();

        $response->assertRedirect(route('enrollments.contracts.sign', $enrollment));
        $this->assertSame('pendiente', $enrollment->status);
        $this->assertEquals(1000000, $enrollment->final_price);
        $this->assertSame('Marta Pérez', $student->guardian_name);
        $this->assertSame(1, $enrollment->contractSignatures()->open()->count());
    }

    public function test_existing_student_can_be_sent_the_link_by_email(): void
    {
        Notification::fake();
        $level = Level::factory()->create();
        $this->publishedTemplate();
        $student = Student::factory()->create(['birth_date' => now()->subYears(30)]);

        $response = $this->actingAs($this->adminUser())
            ->post(route('enrollments.wizard.store'), $this->wizardData($level, ['student_id' => $student->id, 'sign_method' => 'correo']));

        $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();
        $response->assertRedirect(route('enrollments.edit', $enrollment))->assertSessionHas('contractLink');
        $this->assertSame('enviado', $enrollment->contractSignatures()->firstOrFail()->status);
    }

    public function test_enrollment_is_created_without_contracts_when_student_data_is_incomplete(): void
    {
        $level = Level::factory()->create();
        $this->publishedTemplate();
        $student = Student::factory()->create(['birth_date' => null]);

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.wizard.store'), $this->wizardData($level, ['student_id' => $student->id, 'sign_method' => 'despues']))
            ->assertSessionHasNoErrors();

        $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame(0, $enrollment->contractSignatures()->count());
    }

    public function test_wizard_respects_the_level_prerequisite(): void
    {
        $second = Level::factory()->create();
        Level::factory()->create(['course_id' => $second->course_id, 'next_level_id' => $second->id]);
        $student = Student::factory()->create(['birth_date' => now()->subYears(30)]);

        $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.wizard.store'), $this->wizardData($second, ['student_id' => $student->id]))
            ->assertSessionHasErrors('level_id');

        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_cashier_uses_the_regular_form_instead_of_the_wizard(): void
    {
        $this->actingAs($this->cajeroUser())->get(route('enrollments.wizard.create'))->assertForbidden();
        $this->actingAs($this->secretariaUser())->get(route('enrollments.wizard.create'))->assertOk();
    }
}
