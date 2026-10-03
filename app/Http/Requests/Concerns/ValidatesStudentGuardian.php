<?php

namespace App\Http\Requests\Concerns;

use App\Models\Student;
use Illuminate\Validation\Rule;

/**
 * Reglas de los datos personales y del acudiente, compartidas por el
 * formulario de estudiantes y la importación CSV/Excel (parte 6.2 del plan
 * de mejoras). No son obligatorios al guardar: si faltan, el estudiante
 * queda como "Datos incompletos" y no se le pueden enviar contratos.
 */
trait ValidatesStudentGuardian
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function guardianRules(): array
    {
        $phone = ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'];

        return [
            'document_type' => ['nullable', Rule::in(array_keys(Student::DOCUMENT_TYPES))],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_document_type' => ['nullable', Rule::in(array_keys(Student::DOCUMENT_TYPES))],
            'guardian_document' => ['nullable', 'string', 'max:50'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => $phone,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function guardianMessages(): array
    {
        return [
            'guardian_phone.regex' => 'El teléfono del acudiente solo puede tener números, espacios y el indicativo (p. ej. +57 300 123 4567).',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function guardianAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento',
            'birth_date' => 'fecha de nacimiento',
            'guardian_name' => 'nombre del acudiente',
            'guardian_document_type' => 'tipo de documento del acudiente',
            'guardian_document' => 'documento del acudiente',
            'guardian_relationship' => 'parentesco',
            'guardian_email' => 'correo del acudiente',
            'guardian_phone' => 'teléfono del acudiente',
        ];
    }
}
