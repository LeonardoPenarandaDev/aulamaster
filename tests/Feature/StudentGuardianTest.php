<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 6.2 del plan de mejoras: fecha de nacimiento, tipo de documento y
 * datos del acudiente. Si el estudiante es menor y faltan los datos del
 * acudiente (o falta la fecha de nacimiento), queda como "Datos incompletos".
 */
class StudentGuardianTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_minor_is_determined_by_birth_date_on_a_given_day(): void
    {
        $student = Student::factory()->make(['birth_date' => '2010-06-15']);

        $this->assertTrue($student->isMinor(now()->setDate(2028, 6, 14)));
        $this->assertFalse($student->isMinor(now()->setDate(2028, 6, 15)));
        $this->assertFalse(Student::factory()->make(['birth_date' => null])->isMinor());
    }

    public function test_missing_contract_data_for_minors_and_adults(): void
    {
        $this->assertSame(['fecha de nacimiento'], Student::factory()->make(['birth_date' => null])->missingContractData());

        $minor = Student::factory()->make(['birth_date' => now()->subYears(12)]);
        $this->assertSame(['nombre del acudiente', 'correo del acudiente'], $minor->missingContractData());

        $minor->fill(['guardian_name' => 'Marta Pérez', 'guardian_email' => 'marta@correo.test']);
        $this->assertSame([], $minor->missingContractData());

        $this->assertSame([], Student::factory()->make(['birth_date' => now()->subYears(30)])->missingContractData());
    }

    public function test_secretaria_saves_a_minor_with_guardian_data(): void
    {
        $this->actingAs($this->secretariaUser())
            ->post(route('students.store'), [
                'code' => 'EST-700',
                'name' => 'Luis Pérez',
                'document_type' => 'TI',
                'document' => '1098765432',
                'birth_date' => now()->subYears(13)->toDateString(),
                'status' => 'activo',
                'guardian_name' => 'Marta Pérez',
                'guardian_document_type' => 'CC',
                'guardian_document' => '52123456',
                'guardian_relationship' => 'Madre',
                'guardian_email' => 'marta@correo.test',
                'guardian_phone' => '+57 310 555 1234',
            ])
            ->assertSessionHasNoErrors();

        $student = Student::query()->where('code', 'EST-700')->firstOrFail();
        $this->assertTrue($student->isMinor());
        $this->assertSame('Marta Pérez', $student->guardian_name);
        $this->assertSame([], $student->missingContractData());
    }

    public function test_invalid_guardian_data_is_rejected(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('students.store'), [
                'code' => 'EST-701',
                'name' => 'Luis Pérez',
                'document_type' => 'XX',
                'birth_date' => now()->addDay()->toDateString(),
                'status' => 'activo',
                'guardian_email' => 'no-es-correo',
                'guardian_phone' => 'llamar a Marta',
            ])
            ->assertSessionHasErrors(['document_type', 'birth_date', 'guardian_email', 'guardian_phone']);
    }

    public function test_students_list_flags_incomplete_data(): void
    {
        Student::factory()->create(['name' => 'Menor sin acudiente', 'birth_date' => now()->subYears(10)]);

        $this->actingAs($this->adminUser())
            ->get(route('students.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('students.data.0.is_minor', true)
                ->where('students.data.0.missing_contract_data', ['nombre del acudiente', 'correo del acudiente'])
            );
    }

    public function test_import_reads_birth_date_and_guardian_columns(): void
    {
        $file = UploadedFile::fake()->createWithContent('estudiantes.csv',
            "codigo,nombre,tipo_documento,fecha_nacimiento,acudiente_nombre,acudiente_correo,acudiente_telefono\n".
            "EST-010,Luis Pérez,ti,15/08/2012,Marta Pérez,MARTA@correo.test,+57 310 555 1234\n".
            "EST-011,Ana Gómez,CC,1995-04-20,,,\n"
        );

        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHasNoErrors();

        $luis = Student::query()->where('code', 'EST-010')->firstOrFail();
        $this->assertSame('TI', $luis->document_type);
        $this->assertSame('2012-08-15', $luis->birth_date->toDateString());
        $this->assertSame('marta@correo.test', $luis->guardian_email);
        $this->assertSame('1995-04-20', Student::query()->where('code', 'EST-011')->value('birth_date')->toDateString());
    }

    public function test_import_reports_invalid_birth_dates_with_the_row_number(): void
    {
        $file = UploadedFile::fake()->createWithContent('estudiantes.csv',
            "codigo,nombre,fecha_nacimiento\nEST-020,Ana,ayer\n"
        );

        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHasErrors('row_2');

        $this->assertDatabaseMissing('students', ['code' => 'EST-020']);
    }
}
