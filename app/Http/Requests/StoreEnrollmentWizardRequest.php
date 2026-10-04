<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStudentGuardian;
use App\Models\ContractSignature;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Asistente de matrícula (parte 6.3 del plan de mejoras): mismas reglas que
 * la matrícula normal, pero el estudiante puede crearse en el mismo paso y
 * la matrícula siempre empieza como pendiente hasta firmar y pagar.
 */
class StoreEnrollmentWizardRequest extends StoreEnrollmentRequest
{
    use ValidatesStudentGuardian;

    public function authorize(): bool
    {
        return parent::authorize()
            && $this->user()->can('create', Student::class)
            && $this->user()->can('manage', ContractSignature::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studentRules = collect($this->guardianRules())
            ->mapWithKeys(fn (array $rules, string $field) => ["new_student.{$field}" => $rules])
            ->all();

        return [
            ...Arr::except(parent::rules(), ['status']),
            'student_id' => ['nullable', 'required_without:new_student', 'exists:students,id'],
            'new_student' => ['nullable', 'array'],
            'new_student.code' => ['required_with:new_student', 'string', 'max:20', Rule::unique('students', 'code')],
            'new_student.name' => ['required_with:new_student', 'string', 'max:255'],
            'new_student.document' => ['nullable', 'string', 'max:50'],
            'new_student.email' => ['nullable', 'email', 'max:255'],
            'new_student.phone' => ['nullable', 'string', 'max:50'],
            'new_student.address' => ['nullable', 'string', 'max:255'],
            ...$studentRules,
            'photo' => Student::photoRules(required: false),
            'special_clauses' => ['array'],
            'special_clauses.*' => ['nullable', 'string', 'max:5000'],
            'sign_method' => ['required', Rule::in(['oficina', 'correo', 'whatsapp', 'enlace', 'despues'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...collect($this->guardianAttributes())->mapWithKeys(fn (string $label, string $field) => ["new_student.{$field}" => $label])->all(),
            'new_student.code' => 'código',
            'new_student.name' => 'nombre',
            'new_student.email' => 'correo',
            'student_id' => 'estudiante',
        ];
    }
}
