<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('classroom'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('classrooms', 'code')->ignore($this->route('classroom'))],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['disponible', 'no_disponible'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
