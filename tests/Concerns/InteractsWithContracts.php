<?php

namespace Tests\Concerns;

use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Models\Student;

/**
 * Helpers para las pruebas de contratos (parte 6 del plan de mejoras).
 */
trait InteractsWithContracts
{
    /**
     * Una firma dibujada en el lienzo, como la envía el navegador.
     */
    protected function signaturePng(): string
    {
        $image = imagecreatetruecolor(120, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imageline($image, 5, 30, 115, 8, imagecolorallocate($image, 0, 0, 0));

        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /**
     * @param  array<string, mixed>  $studentAttributes
     */
    protected function enrollmentReadyForContracts(array $studentAttributes = [], string $status = 'pendiente'): Enrollment
    {
        $student = Student::factory()->create([
            'birth_date' => now()->subYears(25),
            'document_type' => 'CC',
            'document' => '1020304050',
            ...$studentAttributes,
        ]);

        return Enrollment::factory()->create(['student_id' => $student->id, 'status' => $status]);
    }

    protected function minorStudentAttributes(): array
    {
        return [
            'birth_date' => now()->subYears(13),
            'document_type' => 'TI',
            'guardian_name' => 'Marta Pérez',
            'guardian_document_type' => 'CC',
            'guardian_document' => '52123456',
            'guardian_email' => 'marta@correo.test',
            'guardian_phone' => '+57 310 555 1234',
        ];
    }

    protected function publishedTemplate(array $attributes = []): ContractTemplate
    {
        return ContractTemplate::factory()->published()->create($attributes);
    }
}
