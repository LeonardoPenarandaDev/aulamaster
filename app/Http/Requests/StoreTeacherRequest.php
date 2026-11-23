<?php

namespace App\Http\Requests;

use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Teacher::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('teachers', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'required_if_accepted:portal_access', 'string', 'max:50'],
            'email' => [
                'nullable',
                'required_if_accepted:portal_access',
                'email',
                'max:255',
                Rule::when($this->boolean('portal_access'), [Rule::unique('users', 'email')]),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
            'global_levels' => ['nullable', 'array'],
            'global_levels.*' => ['string', Rule::in(Level::globalLevelOptions()->keys()->all())],
            'portal_access' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required_if_accepted' => 'El documento es obligatorio para crear el acceso: será la contraseña inicial.',
            'email.required_if_accepted' => 'El correo es obligatorio para crear el acceso: será el usuario de ingreso.',
        ];
    }
}
