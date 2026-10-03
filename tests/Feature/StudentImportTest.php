<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El admin puede registrar estudiantes de forma masiva subiendo un CSV o
 * Excel. La importación es todo o nada: una fila inválida cancela todo.
 */
class StudentImportTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function csv(string $content, string $name = 'estudiantes.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_admin_can_import_students_from_csv(): void
    {
        $file = $this->csv(
            "codigo,nombre,documento,correo,telefono,direccion,estado,contrasena\n".
            "EST-001,Ana Gómez,111,ana@instituto.test,300,Calle 1,activo,\n".
            "EST-002,Luis Pérez,222,LUIS@instituto.test,,,Inactivo,ClaveSegura123\n".
            ",,,,,,,\n"
        );

        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertRedirect(route('students.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Se importaron 2 estudiantes (1 con acceso al portal).');

        $this->assertDatabaseHas('students', ['code' => 'EST-001', 'name' => 'Ana Gómez', 'status' => 'activo', 'user_id' => null]);

        $luis = Student::query()->where('code', 'EST-002')->firstOrFail();
        $this->assertSame('inactivo', $luis->status);
        $this->assertSame('luis@instituto.test', $luis->email);
        $this->assertTrue($luis->user->hasRole('estudiante'));
        $this->assertTrue(Hash::check('ClaveSegura123', $luis->user->password));
    }

    public function test_headers_with_accents_and_semicolon_delimiter_are_accepted(): void
    {
        $file = $this->csv(
            "\xEF\xBB\xBFCódigo;Nombre;Teléfono;Dirección\n".
            "EST-010;María López;3001234567;Carrera 5\n"
        );

        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'code' => 'EST-010',
            'name' => 'María López',
            'phone' => '3001234567',
            'address' => 'Carrera 5',
            'status' => 'activo',
        ]);
    }

    public function test_no_student_is_created_when_any_row_is_invalid(): void
    {
        Student::factory()->create(['code' => 'EST-001']);

        $file = $this->csv(
            "codigo,nombre,correo,contrasena\n".
            "EST-100,Válido,,\n".
            "EST-001,Código existente,,\n".
            "EST-101,,,\n".
            "EST-100,Repetido,,\n".
            "EST-102,Sin correo,,ClaveSegura123\n"
        );

        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHasErrors(['row_3', 'row_4', 'row_5', 'row_6'])
            ->assertSessionDoesntHaveErrors(['row_2']);

        $this->assertSame(1, Student::query()->count());
    }

    public function test_file_without_students_is_rejected(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('students.import.store'), ['file' => $this->csv("codigo,nombre\n")])
            ->assertSessionHasErrors('file');
    }

    public function test_template_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('students.import.template'))
            ->assertOk()
            ->assertDownload('plantilla_estudiantes.csv');

        $this->assertStringContainsString('codigo,nombre,tipo_documento,documento,fecha_nacimiento,correo', $response->streamedContent());
    }

    public function test_non_admin_cannot_import_students(): void
    {
        $this->actingAs($this->teacherUser())
            ->post(route('students.import.store'), ['file' => $this->csv("codigo,nombre\nEST-1,Ana\n")])
            ->assertForbidden();

        $this->assertSame(0, Student::query()->count());
    }
}
