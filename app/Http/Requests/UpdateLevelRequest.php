<?php

namespace App\Http\Requests;

use App\Rules\ValidNextLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('level'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'next_level_id' => [
                'nullable',
                'exists:levels,id',
                Rule::unique('levels', 'next_level_id')->ignore($this->route('level')),
                new ValidNextLevel($this->input('course_id'), $this->route('level')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'code' => ['required', 'string', 'max:30', Rule::unique('levels', 'code')->ignore($this->route('level'))],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0'],
            'monthly_hours' => ['nullable', 'numeric', 'min:0'],
            'required_hours' => ['required', 'numeric', 'min:0'],
            'minimum_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'apply_monthly_fee_to_active' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'next_level_id.unique' => 'Ese nivel ya es el siguiente de otro nivel.',
            'color.regex' => 'El color debe tener el formato #RRGGBB.',
        ];
    }
}
