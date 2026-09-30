<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetStudentsWithAbsences
{
    /**
     * Estudiantes que faltaron (ausente) o no pudieron asistir (excusado) a
     * clases entre dos fechas, agrupados por estudiante para que el
     * instituto pueda contactarlos. Igual que en RecalculateEnrollmentHours,
     * se usa el estado efectivo: una falta corregida a "presente" no cuenta.
     *
     * @param  array<int, string>  $statuses
     * @return Collection<int, array{student: array{id: int, code: string, name: string, phone: ?string, email: ?string}, absent_count: int, excused_count: int, last_missed_date: string, last_present_date: ?string, levels: array<int, string>, missed: array<int, array{date: string, status: string, level: string}>}>
     */
    public function handle(Carbon $from, Carbon $to, array $statuses = ['ausente', 'excusado'], ?int $levelId = null): Collection
    {
        $missed = Attendance::query()
            ->with(['enrollment.student:id,code,name,phone,email', 'classSession.level.course:id,name', 'corrections'])
            ->whereDate('class_date', '>=', $from)
            ->whereDate('class_date', '<=', $to)
            ->when($levelId, fn ($query, $id) => $query->whereHas('classSession', fn ($q) => $q->where('level_id', $id)))
            ->orderByDesc('class_date')
            ->get()
            ->filter(fn (Attendance $attendance) => in_array($attendance->effective_status, $statuses, true));

        $lastPresentByStudent = $this->lastPresentDateByStudent($missed->pluck('enrollment.student_id')->unique()->all());

        return $missed
            ->groupBy('enrollment.student_id')
            ->map(function (Collection $attendances) use ($lastPresentByStudent) {
                $student = $attendances->first()->enrollment->student;

                return [
                    'student' => $student->only(['id', 'code', 'name', 'phone', 'email']),
                    'absent_count' => $attendances->where('effective_status', 'ausente')->count(),
                    'excused_count' => $attendances->where('effective_status', 'excusado')->count(),
                    'last_missed_date' => $attendances->first()->class_date->toDateString(),
                    'last_present_date' => $lastPresentByStudent[$student->id] ?? null,
                    'levels' => $attendances->map(fn (Attendance $attendance) => $this->levelName($attendance))->unique()->values()->all(),
                    'missed' => $attendances->map(fn (Attendance $attendance) => [
                        'date' => $attendance->class_date->toDateString(),
                        'status' => $attendance->effective_status,
                        'level' => $this->levelName($attendance),
                    ])->values()->all(),
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['absent_count'] + $row['excused_count'], $row['last_missed_date']])
            ->values();
    }

    /**
     * Última clase a la que cada estudiante sí asistió, sin límite de fechas:
     * ayuda a detectar a quien dejó de venir por completo.
     *
     * @param  array<int, int>  $studentIds
     * @return array<int, string>
     */
    private function lastPresentDateByStudent(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return Attendance::query()
            ->with(['enrollment:id,student_id', 'corrections'])
            ->whereHas('enrollment', fn ($query) => $query->whereIn('student_id', $studentIds))
            ->orderByDesc('class_date')
            ->get()
            ->filter(fn (Attendance $attendance) => $attendance->effective_status === 'presente')
            ->unique('enrollment.student_id')
            ->mapWithKeys(fn (Attendance $attendance) => [$attendance->enrollment->student_id => $attendance->class_date->toDateString()])
            ->all();
    }

    private function levelName(Attendance $attendance): string
    {
        $level = $attendance->classSession->level;

        return trim("{$level->course?->name} {$level->name}");
    }
}
