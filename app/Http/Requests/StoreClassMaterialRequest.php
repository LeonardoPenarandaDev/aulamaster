<?php

namespace App\Http\Requests;

use App\Models\ClassMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [ClassMaterial::class, $this->route('class_session')]);
    }

    /**
     * Sin tipo, el material es un enlace (como antes de la parte 12).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => 'enlace']);
        }
    }

    /**
     * Get the validation rules that apply to the request. Se revisa el tipo
     * real del archivo, no solo su extensión (parte 12 del plan de mejoras).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(ClassMaterial::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required_if:type,youtube,enlace', 'nullable', 'url:http,https', 'max:2048'],
            'file' => [
                'required_if:type,pdf,imagen',
                'nullable',
                'file',
                ...match ($this->input('type')) {
                    'pdf' => ['mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('materials.pdf_max_kb')],
                    'imagen' => ['mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('materials.image_max_kb')],
                    default => ['prohibited'],
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') === 'youtube' && ! $validator->errors()->has('url') && ! ClassMaterial::youtubeIdFrom($this->input('url'))) {
                    $validator->errors()->add('url', 'No es un enlace de un video de YouTube.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tooBig = 'El archivo pesa más de :max KB. Comprímelo (por ejemplo en ilovepdf.com o squoosh.app) o compártelo como enlace de Google Drive.';

        return [
            'file.max' => $tooBig,
            'file.mimes' => 'El archivo no es del tipo elegido.',
            'file.mimetypes' => 'El archivo no es del tipo elegido.',
            'file.required_if' => 'Elige el archivo que vas a subir.',
            'url.required_if' => 'Escribe el enlace.',
        ];
    }
}
