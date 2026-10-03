<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignContractInOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('signInOffice', $this->route('contract_signature'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $photo = ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('contracts.id_photo_max_kb')];
        $isOptional = $this->route('contract_signature')->template->acceptance_mode === 'opcional';

        return [
            'signature_png' => ['required', 'string', 'max:2000000'],
            'decision' => [$isOptional ? 'required' : 'nullable', Rule::in(['acepta', 'no_acepta'])],
            'signer_name' => ['required', 'string', 'max:255'],
            'signer_document' => ['required', 'string', 'max:60'],
            'signer_role' => ['required', Rule::in(['alumno', 'acudiente'])],
            'signer_email' => ['nullable', 'email', 'max:255'],
            'identity_verified' => ['accepted'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'id_front' => $photo,
            'id_back' => $photo,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identity_verified.accepted' => 'Confirma que verificaste el documento de identidad.',
            'decision.required' => 'Indica si el firmante acepta o no.',
            'signature_png.required' => 'Falta la firma.',
            'id_front.max' => 'La foto no puede pesar más de 5 MB.',
            'id_back.max' => 'La foto no puede pesar más de 5 MB.',
        ];
    }
}
