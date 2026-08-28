<?php

namespace App\Http\Requests;

use App\Actions\Evaluations\CheckEvaluationEligibility;
use App\Actions\Recovery\DetermineRecoveryTerms;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEvaluationResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', EvaluationResult::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'evaluated_at' => ['required', 'date'],
            'grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'payment_confirmed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance. Blocks presentation when the
     * enrollment is "NO APTO PARA PRESENTAR" (sección 20) and enforces the
     * recovery attempt limits and payment requirement (secciones 22 y 24).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Enrollment $enrollment */
            $enrollment = $this->route('enrollment');

            foreach (app(CheckEvaluationEligibility::class)->handle($enrollment) as $requirement) {
                $validator->errors()->add('eligibility', "No apto para presentar. Falta: {$requirement}.");
            }

            $evaluation = Evaluation::find($this->input('evaluation_id'));

            if (! $evaluation) {
                return;
            }

            $terms = app(DetermineRecoveryTerms::class)->handle($evaluation, $enrollment);

            if ($terms['blocked']) {
                $validator->errors()->add('eligibility', $terms['block_reason']);

                return;
            }

            if ($terms['cost'] > 0 && ! $this->boolean('payment_confirmed')) {
                $validator->errors()->add(
                    'payment_confirmed',
                    'Esta recuperación tiene un costo de $'.number_format($terms['cost'], 0, ',', '.').'. Confirma que el pago fue realizado antes de registrar el resultado.'
                );
            }
        });
    }
}
