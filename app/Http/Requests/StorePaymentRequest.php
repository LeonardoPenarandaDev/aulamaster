<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * Sin tipo, el pago queda como "otro" y no activa la mora.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => 'otro']);
        }
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
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'type' => ['required', Rule::in(array_keys(Payment::TYPES))],
            'period' => ['nullable', 'required_if:type,mensualidad', 'date_format:Y-m'],
            'due_date' => ['nullable', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'final_amount' => ['required', 'numeric', 'min:0'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'referral_id' => ['nullable', 'exists:referrals,id'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pendiente', 'pagado', 'vencido', 'anulado'])],
        ];
    }
}
