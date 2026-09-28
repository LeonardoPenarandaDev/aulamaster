<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Attendance::class, $this->route('class_session')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.enrollment_id' => [
                'required',
                Rule::exists('enrollments', 'id')
                    ->where('level_id', $this->route('class_session')->level_id)
                    ->whereIn('status', ['activa', 'en_recuperacion', 'extendida']),
            ],
            'records.*.status' => ['required', Rule::in(['presente', 'ausente', 'excusado'])],
        ];
    }
}
