<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Evaluation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'competency' => ['nullable', 'string', 'max:255'],
            'minimum_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
