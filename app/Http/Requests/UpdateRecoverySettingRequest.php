<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecoverySettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'free_attempts' => ['required', 'integer', 'min:0', 'max:10'],
            'max_paid_attempts' => ['required', 'integer', 'min:0', 'max:10'],
            'recovery_price' => ['required', 'numeric', 'min:0'],
            'recovery_period_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
