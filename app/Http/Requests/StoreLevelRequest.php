<?php

namespace App\Http\Requests;

use App\Models\Level;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Level::class);
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('levels', 'code')],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0'],
            'monthly_hours' => ['nullable', 'numeric', 'min:0'],
            'required_hours' => ['required', 'numeric', 'min:0'],
            'minimum_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
