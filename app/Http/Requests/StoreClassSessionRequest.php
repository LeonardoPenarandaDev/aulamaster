<?php

namespace App\Http\Requests;

use App\Actions\Scheduling\CheckClassSessionConflicts;
use App\Models\ClassSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ClassSession::class);
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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $conflicts = app(CheckClassSessionConflicts::class)->handle(
                classroomId: (int) $this->input('classroom_id'),
                teacherId: (int) $this->input('teacher_id'),
                date: $this->input('date'),
                startTime: $this->input('start_time'),
                endTime: $this->input('end_time'),
            );

            foreach ($conflicts as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }
}
