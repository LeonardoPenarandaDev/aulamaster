<?php

namespace App\Actions\Contracts;

use App\Models\Student;

class ResolveContractSigner
{
    /**
     * Quién firma los contratos del estudiante hoy: si es menor de edad,
     * solo el acudiente (parte 6.2 del plan de mejoras).
     *
     * @return array{role: string, name: string|null, document: string|null, email: string|null, phone: string|null}
     */
    public function handle(Student $student): array
    {
        if ($student->isMinor()) {
            return [
                'role' => 'acudiente',
                'name' => $student->guardian_name,
                'document' => trim(($student->guardian_document_type ? "{$student->guardian_document_type} " : '').($student->guardian_document ?? '')) ?: null,
                'email' => $student->guardian_email,
                'phone' => $student->guardian_phone,
            ];
        }

        return [
            'role' => 'alumno',
            'name' => $student->name,
            'document' => trim(($student->document_type ? "{$student->document_type} " : '').($student->document ?? '')) ?: null,
            'email' => $student->email,
            'phone' => $student->phone,
        ];
    }
}
