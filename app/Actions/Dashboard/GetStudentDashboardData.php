<?php

namespace App\Actions\Dashboard;

use App\Actions\Attendance\GetWeeklyHoursSummary;
use App\Actions\Payments\GetAccountStatement;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;

class GetStudentDashboardData
{
    public function __construct(
        protected GetAccountStatement $getAccountStatement,
        protected GetWeeklyHoursSummary $getWeeklyHoursSummary,
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
                'level_path' => $this->levelPath($student),
                'account' => $this->getAccountStatement->handle($student),
                'pending_payments' => $this->pendingPayments($student),
                'certificates' => $this->certificates($student),
            ];
        }

        $nextSession = ClassSession::query()
            ->where('level_id', $enrollment->level_id)
            ->where(fn ($query) => $query
                ->where('date', '>', today())
                ->orWhere(fn ($query) => $query->where('date', today())->where('start_time', '>=', now()->format('H:i:s')))
            )
            ->with('classroom:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        $evaluationsTotal = $enrollment->level->evaluations()->where('status', 'activo')->count();
        $evaluationsPresented = $enrollment->evaluationResults()->distinct('evaluation_id')->count('evaluation_id');

        return [
            'has_enrollment' => true,
            'level_path' => $this->levelPath($student),
            'enrollment' => [
                'course' => $enrollment->level->course->name,
                'level' => $enrollment->level->name,
                'status' => $enrollment->status,
                'accumulated_hours' => $enrollment->accumulated_hours,
                'required_hours' => $enrollment->required_hours,
                'progress_percentage' => $enrollment->progress_percentage,
                'weekly_hours' => $enrollment->weekly_hours,
            ],
            'weekly_summary' => $this->getWeeklyHoursSummary->handle($enrollment),
            'catch_up' => $this->getWeeklyHoursSummary->catchUp($enrollment),
            'next_session' => $nextSession ? [
                'date' => $nextSession->date->toDateString(),
                'start_time' => substr($nextSession->start_time, 0, 5),
                'end_time' => substr($nextSession->end_time, 0, 5),
                'classroom' => $nextSession->classroom->name,
                'modality' => $nextSession->modality,
                'meeting_url' => $nextSession->meeting_url,
            ] : null,
            'evaluations' => ['presented' => $evaluationsPresented, 'total' => $evaluationsTotal],
            'account' => $this->getAccountStatement->handle($student),
            'pending_payments' => $this->pendingPayments($student),
            'certificates' => $this->certificates($student),
        ];
    }

    /**
     * Ruta de niveles del curso actual del estudiante, con el color de cada
     * nivel y su estado: aprobado, actual o próximo (parte 5 del plan de
     * mejoras).
     *
     * @return array<int, array{id: int, name: string, color: string, state: string}>
     */
    protected function levelPath(Student $student): array
    {
        $currentEnrollment = $student->currentEnrollment();

        if (! $currentEnrollment) {
            return [];
        }

        $approvedLevelIds = $student->enrollments()->where('status', 'aprobada')->pluck('level_id')->all();

        return $currentEnrollment->level->route()
            ->map(fn (Level $level) => [
                'id' => $level->id,
                'name' => $level->name,
                'color' => $level->color,
                'state' => match (true) {
                    $level->id === $currentEnrollment->level_id && $currentEnrollment->status !== 'aprobada' => 'actual',
                    in_array($level->id, $approvedLevelIds, true) => 'aprobado',
                    default => 'proximo',
                },
            ])
            ->values()
            ->all();
    }

    /**
     * Niveles aprobados (horas completas y evaluaciones aprobadas), cuyo
     * certificado el estudiante puede descargar.
     *
     * @return array<int, array{enrollment_id: int, course: string, level: string, finished_at: string|null}>
     */
    protected function certificates(Student $student): array
    {
        return Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'aprobada')
            ->with('level.course:id,name')
            ->orderByDesc('actual_end_date')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'enrollment_id' => $enrollment->id,
                'course' => $enrollment->level->course->name,
                'level' => $enrollment->level->name,
                'finished_at' => $enrollment->actual_end_date?->toDateString(),
            ])
            ->all();
    }

    /**
     * Pagos pendientes del estudiante, para que pueda pagarlos en línea con
     * Wompi desde su propio dashboard (Fase 17 del checklist).
     *
     * @return array<int, array{id: int, concept: string, final_amount: float, status: string}>
     */
    protected function pendingPayments(Student $student): array
    {
        return $student->payments()
            ->whereIn('status', ['pendiente', 'vencido'])
            ->orderBy('id')
            ->get(['id', 'concept', 'final_amount', 'status'])
            ->toArray();
    }
}
