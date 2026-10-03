<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\Level;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'weekly_hours' => ['required', 'numeric', 'min:1', 'max:60'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'referral_id' => ['nullable', 'exists:referrals,id'],
            'skip_prerequisite' => ['boolean'],
        ];
    }

    /**
     * No se puede matricular en un nivel sin haber aprobado el anterior de
     * la ruta. Solo el admin puede omitir el requisito, y eso queda en la
     * auditoría de la matrícula (parte 5 del plan de mejoras).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['student_id', 'level_id'])) {
                    return;
                }

                if (! $this->isMissingPrerequisite() || $this->prerequisiteWaived()) {
                    return;
                }

                $previousLevel = Level::find($this->input('level_id'))->previousLevel;

                $validator->errors()->add(
                    'level_id',
                    "El estudiante debe aprobar primero {$previousLevel->name} para matricularse en este nivel.",
                );
            },
        ];
    }

    /**
     * Whether the selected level has a previous level the student hasn't approved.
     */
    public function isMissingPrerequisite(): bool
    {
        $previousLevel = Level::find($this->input('level_id'))?->previousLevel;

        if (! $previousLevel) {
            return false;
        }

        return ! Enrollment::query()
            ->where('student_id', $this->input('student_id'))
            ->where('level_id', $previousLevel->id)
            ->where('status', 'aprobada')
            ->exists();
    }

    /**
     * Whether an admin chose to skip the prerequisite check.
     */
    public function prerequisiteWaived(): bool
    {
        return $this->boolean('skip_prerequisite') && $this->user()->hasRole('admin');
    }
}
