<?php

namespace App\Actions\Dashboard;

use App\Actions\Payments\GetAccountStatement;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;

class GetStudentDashboardData
{
    public function __construct(
        protected GetAccountStatement $getAccountStatement,
    ) {}

    /**
     * Datos del dashboard del estudiante (secciones 18 y 37 del plan): su
     * matrícula activa, próxima clase, horas, evaluaciones y estado de cuenta.
     *
     * @return array<string, mixed>
     */
    public function handle(Student $student): array
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->with('level.course')
            ->latest('enrolled_at')
            ->first();

        if (! $enrollment) {
            return [
                'has_enrollment' => false,
                'account' => $this->getAccountStatement->handle($student),
                'pending_payments' => $this->pendingPayments($student),
            ];
        }

        $nextSession = ClassSession::query()
            ->where('level_id', $enrollment->level_id)
            ->where(fn ($query) => $query
                ->where('date', '>', today())
                ->orWhere(fn ($query) => $query->where('date', today())->where('start_time', '>=', now()->format('H:i:s')))
            )
            ->with(['teacher:id,name', 'classroom:id,name'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        $evaluationsTotal = $enrollment->level->evaluations()->where('status', 'activo')->count();
        $evaluationsPresented = $enrollment->evaluationResults()->distinct('evaluation_id')->count('evaluation_id');

        return [
            'has_enrollment' => true,
            'enrollment' => [
                'course' => $enrollment->level->course->name,
                'level' => $enrollment->level->name,
                'status' => $enrollment->status,
                'accumulated_hours' => $enrollment->accumulated_hours,
                'required_hours' => $enrollment->required_hours,
                'progress_percentage' => $enrollment->progress_percentage,
            ],
            'next_session' => $nextSession ? [
                'date' => $nextSession->date->toDateString(),
                'start_time' => substr($nextSession->start_time, 0, 5),
                'end_time' => substr($nextSession->end_time, 0, 5),
                'classroom' => $nextSession->classroom->name,
                'teacher' => $nextSession->teacher->name,
            ] : null,
            'evaluations' => ['presented' => $evaluationsPresented, 'total' => $evaluationsTotal],
            'account' => $this->getAccountStatement->handle($student),
            'pending_payments' => $this->pendingPayments($student),
        ];
    }

    /**
     * Pagos pendientes del estudiante, para que pueda pagarlos en línea con
     * Wompi desde su propio dashboard (Fase 17 del checklist).
     *
     * @return array<int, array{id: int, concept: string, final_amount: float}>
     */
    protected function pendingPayments(Student $student): array
    {
        return $student->payments()
            ->where('status', 'pendiente')
            ->orderBy('id')
            ->get(['id', 'concept', 'final_amount'])
            ->toArray();
    }
}
