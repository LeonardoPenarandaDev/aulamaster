<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStudentGuardian;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
{
    use ValidatesStudentGuardian;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('students', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'required_with:password',
                'email',
                'max:255',
                Rule::when($this->filled('password'), [Rule::unique('users', 'email')]),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
            ...$this->guardianRules(),
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->guardianMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->guardianAttributes();
    }
}
