<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }

    /**
     * Get the validation rules that apply to the request. Solo se permite
     * actualizar el estado y los datos del pago, no los montos (el valor
     * facturado es histórico, sección 32 del plan).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pendiente', 'pagado', 'vencido', 'anulado'])],
            'final_amount' => [Rule::prohibitedIf(! $this->canCorrectAmount()), 'nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * El cajero puede corregir el valor de una mensualidad mientras no se
     * haya pagado; el cambio queda en la auditoría (parte 8 del plan de
     * mejoras). Los demás montos son históricos y no se editan.
     */
    public function canCorrectAmount(): bool
    {
        $payment = $this->route('payment');

        return $payment->type === 'mensualidad' && in_array($payment->status, ['pendiente', 'vencido'], true);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'final_amount.prohibited' => 'Solo se puede corregir el valor de una mensualidad que todavía no está pagada.',
        ];
    }
}
