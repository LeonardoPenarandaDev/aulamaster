<?php

namespace App\Http\Requests;

use App\Models\Referral;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Referral::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'referrer_student_id' => ['required', 'exists:students,id', 'different:referred_student_id'],
            'referred_student_id' => ['required', 'exists:students,id', 'unique:referrals,referred_student_id'],
            'referrer_discount' => ['required', 'numeric', 'min:0'],
            'referred_discount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
