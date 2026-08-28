<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Enrollment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'enrolled_at' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'estimated_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in([
                'pendiente', 'activa', 'en_recuperacion', 'extendida',
                'finalizada', 'cancelada', 'aprobada', 'reprobada',
            ])],
            'required_hours' => ['required', 'numeric', 'min:0'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'referral_id' => ['nullable', 'exists:referrals,id'],
        ];
    }
}
