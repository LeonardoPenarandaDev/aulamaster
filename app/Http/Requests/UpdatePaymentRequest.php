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
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
