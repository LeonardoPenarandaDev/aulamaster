<?php

namespace App\Http\Requests;

use App\Actions\Scheduling\CheckClassSessionConflicts;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClassSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('class_session'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'exists:levels,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'modality' => ['sometimes', Rule::in(['presencial', 'virtual'])],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2048'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['programada', 'dictada', 'cancelada', 'reprogramada'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('status') === 'cancelada') {
                return;
            }

            $conflicts = app(CheckClassSessionConflicts::class)->handle(
                classroomId: (int) $this->input('classroom_id'),
                teacherId: (int) $this->input('teacher_id'),
                date: $this->input('date'),
                startTime: $this->input('start_time'),
                endTime: $this->input('end_time'),
                modality: $this->input('modality', 'presencial'),
                ignoreClassSessionId: $this->route('class_session')->id,
            );

            foreach ($conflicts as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }
}
