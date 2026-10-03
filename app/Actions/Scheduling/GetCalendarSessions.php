<?php

namespace App\Actions\Scheduling;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class GetCalendarSessions
{
    /**
     * Clases de un rango de fechas para el calendario, según el rol (parte
     * 10 del plan de mejoras):
     * - Estudiante: las de su nivel, con asistió / faltó / próxima.
     * - Docente: las suyas.
     * - Admin y coordinador: todas, con filtros por aula, docente y nivel.
     *
     * @param  array{classroom_id?: int|null, teacher_id?: int|null, level_id?: int|null}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function handle(User $user, CarbonInterface $from, CarbonInterface $to, array $filters = []): array
    {
        $query = ClassSession::query()
            ->with(['level:id,name,color,course_id', 'level.course:id,name', 'classroom:id,name', 'teacher:id,name,user_id'])
            ->withCount('materials')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time');

        $isStaff = $user->hasRole(['admin', 'coordinador']);
        $isTeacher = ! $isStaff && $user->hasRole('profesor');
        $enrollment = null;

        if ($isStaff) {
            $query
                ->when($filters['classroom_id'] ?? null, fn (Builder $q, $id) => $q->where('classroom_id', $id))
                ->when($filters['teacher_id'] ?? null, fn (Builder $q, $id) => $q->where('teacher_id', $id))
                ->when($filters['level_id'] ?? null, fn (Builder $q, $id) => $q->where('level_id', $id));
        } elseif ($isTeacher) {
            $query->where('teacher_id', $user->teacher?->id ?? 0);
        } else {
            $enrollment = $user->student
                ? Enrollment::query()
                    ->where('student_id', $user->student->id)
                    ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
                    ->latest('enrolled_at')
                    ->first()
                : null;

            $query->where('level_id', $enrollment?->level_id ?? 0);
        }

        $attendanceBySession = $enrollment
            ? Attendance::query()
                ->where('enrollment_id', $enrollment->id)
                ->with('corrections')
                ->get()
                ->mapWithKeys(fn (Attendance $attendance) => [$attendance->class_session_id => $attendance->effective_status])
            : collect();

        return $query->get()->map(function (ClassSession $session) use ($user, $isStaff, $isTeacher, $enrollment, $attendanceBySession) {
            $start = $session->date->copy()->setTimeFromTimeString($session->start_time);
            $end = $session->date->copy()->setTimeFromTimeString($session->end_time);
            $isOwnClass = $session->teacher?->user_id === $user->id;

            return [
                'id' => $session->id,
                'title' => trim(($session->level->course?->name ?? '').' '.$session->level->name),
                'date' => $session->date->toDateString(),
                'start' => substr($session->start_time, 0, 5),
                'end' => substr($session->end_time, 0, 5),
                'color' => $session->level->color,
                'classroom' => $session->classroom?->name,
                'teacher' => $enrollment ? null : ($session->teacher?->name ?? 'Sin docente'),
                'has_teacher' => $session->teacher_id !== null,
                'modality' => $session->modality,
                'meeting_url' => $session->meeting_url,
                'status' => $session->status,
                'notes' => $session->notes,
                'materials_count' => $session->materials_count,
                'student_status' => $enrollment ? match (true) {
                    $session->status === 'cancelada' => 'cancelada',
                    isset($attendanceBySession[$session->id]) => $attendanceBySession[$session->id] === 'presente' ? 'asistio' : 'falto',
                    $end->isFuture() => 'proxima',
                    default => 'sin_registro',
                } : null,
                'can' => [
                    'edit' => $isStaff,
                    'take_attendance' => ($isStaff || ($isTeacher && $isOwnClass)) && $session->teacher_id !== null && $session->status !== 'cancelada',
                    'materials' => $isStaff || ($isTeacher && $isOwnClass),
                    'meeting_url' => $isTeacher && $isOwnClass && $session->modality === 'virtual',
                    'join' => $enrollment !== null && $session->modality === 'virtual' && $session->meeting_url && ! $end->isPast(),
                    'student_materials' => $enrollment !== null && $session->materials_count > 0 && ($attendanceBySession[$session->id] ?? null) === 'presente',
                ],
            ];
        })->all();
    }
}
