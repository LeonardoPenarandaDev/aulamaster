<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * El docente no puede tomar asistencia a un estudiante en mora (parte 8
     * del plan de mejoras). El admin sí, para reconocerle una clase.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->user()->hasRole('admin')) {
                    return;
                }

                $enrollments = Enrollment::query()
                    ->with('student:id,name')
                    ->whereIn('id', collect($this->input('records'))->pluck('enrollment_id'))
                    ->get();

                $blocked = Student::blockedForDebtIds($enrollments->pluck('student_id')->all());

                foreach ($enrollments->whereIn('student_id', $blocked) as $enrollment) {
                    $validator->errors()->add('records', "No se puede registrar la asistencia de {$enrollment->student->name}: tiene pagos vencidos.");
                }
            },
        ];
    }
}
