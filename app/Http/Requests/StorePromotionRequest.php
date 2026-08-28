<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Promotion::class);
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
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in(['fijo', 'porcentaje'])],
            'value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'level_id' => ['nullable', 'exists:levels,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
