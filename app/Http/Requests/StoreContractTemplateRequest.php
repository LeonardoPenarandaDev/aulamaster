<?php

namespace App\Http\Requests;

use App\Models\ContractTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ContractTemplate::class);
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
            'type' => ['required', Rule::in(array_keys(ContractTemplate::TYPES))],
            'body' => ['required', 'string', 'max:100000'],
            'acceptance_mode' => ['required', Rule::in(['obligatorio', 'opcional'])],
            'scope' => ['required', Rule::in(['matricula', 'alumno'])],
            'requires_guardian' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'body' => 'texto del contrato',
            'acceptance_mode' => 'aceptación',
            'scope' => 'alcance',
        ];
    }
}
