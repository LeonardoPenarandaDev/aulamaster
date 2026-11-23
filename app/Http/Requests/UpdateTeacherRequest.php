<?php

namespace App\Http\Requests;

use App\Models\Level;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('teacher'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('teachers', 'code')->ignore($this->route('teacher'))],
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:50'],
            'email' => [
                Rule::requiredIf(fn (): bool => $this->hasOrCreatesAccount()),
                'nullable',
                'email',
                'max:255',
                Rule::when(
                    $this->hasOrCreatesAccount(),
                    [Rule::unique('users', 'email')->ignore($this->route('teacher')->user_id)],
                ),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
            'global_levels' => ['nullable', 'array'],
            'global_levels.*' => ['string', Rule::in(Level::globalLevelOptions()->keys()->all())],
            'password' => ['nullable', 'string', 'min:6', 'max:255', 'confirmed'],
        ];
    }

    /**
     * El profesor ya tiene cuenta de ingreso, o se le crea al definir una contraseña.
     */
    private function hasOrCreatesAccount(): bool
    {
        return $this->route('teacher')->user_id !== null || $this->filled('password');
    }
}
