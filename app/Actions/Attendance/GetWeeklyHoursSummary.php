<?php

namespace App\Actions\Attendance;

use App\Models\Enrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetWeeklyHoursSummary
{
    /**
     * Horas vistas por semana (lunes a domingo) frente a la intensidad
     * horaria semanal contratada en la matrícula, desde la semana actual
     * hacia atrás. Igual que en RecalculateEnrollmentHours, solo cuentan
     * las asistencias cuyo estado efectivo es "presente".
     *
     * @return array<int, array{week_start: string, week_end: string, is_current: bool, target_hours: float, attended_hours: float, pending_hours: float}>
     */
    public function handle(Enrollment $enrollment, int $weeks = 5): array
    {
        $currentWeekStart = today()->startOfWeek(Carbon::MONDAY);
        $oldestWeekStart = $currentWeekStart->copy()->subWeeks($weeks - 1);
        $targetHours = (float) $enrollment->weekly_hours;

        $attendedByWeek = $this->presentHoursByWeek($enrollment, $oldestWeekStart);

        $summary = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $weekStart = $currentWeekStart->copy()->subWeeks($offset);

            if ($weekStart->copy()->endOfWeek(Carbon::SUNDAY)->lessThan($enrollment->start_date)) {
                break;
            }

            $attendedHours = (float) ($attendedByWeek[$weekStart->toDateString()] ?? 0);

            $summary[] = [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'is_current' => $offset === 0,
                'target_hours' => $targetHours,
                'attended_hours' => $attendedHours,
                'pending_hours' => max(0, $targetHours - $attendedHours),
            ];
        }

        return $summary;
    }

    /**
     * Horas por recuperar: lo que faltó en las semanas ya terminadas desde
     * el inicio de la matrícula (las horas extra de una semana compensan las
     * que faltaron en otra), y cuántas horas necesita el estudiante esta
     * semana para cumplir la intensidad y además ponerse al día.
     *
     * @return array{backlog_hours: float, needed_this_week: float}
     */
    public function catchUp(Enrollment $enrollment): array
    {
        $currentWeekStart = today()->startOfWeek(Carbon::MONDAY);
        $firstWeekStart = $enrollment->start_date->copy()->startOfWeek(Carbon::MONDAY);
        $targetHours = (float) $enrollment->weekly_hours;

        $attendedByWeek = $this->presentHoursByWeek($enrollment, $firstWeekStart);
        $attendedThisWeek = (float) ($attendedByWeek[$currentWeekStart->toDateString()] ?? 0);
        $attendedBeforeThisWeek = (float) $attendedByWeek
            ->filter(fn ($hours, $weekStart) => $weekStart < $currentWeekStart->toDateString())
            ->sum();

        $completedWeeks = $firstWeekStart->lessThan($currentWeekStart)
            ? (int) $firstWeekStart->diffInWeeks($currentWeekStart)
            : 0;

        $backlogHours = max(0, $targetHours * $completedWeeks - $attendedBeforeThisWeek);

        return [
            'backlog_hours' => $backlogHours,
            'needed_this_week' => max(0, $targetHours + $backlogHours - $attendedThisWeek),
        ];
    }

    /**
     * @return Collection<string, float> horas presentes, indexadas por el lunes de cada semana
     */
    private function presentHoursByWeek(Enrollment $enrollment, Carbon $since): Collection
    {
        return $enrollment->attendances()
            ->whereDate('class_date', '>=', $since)
            ->with(['classSession', 'corrections'])
            ->get()
            ->filter(fn ($attendance) => $attendance->effective_status === 'presente')
            ->groupBy(fn ($attendance) => $attendance->class_date->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
            ->map(fn ($attendances) => (float) $attendances->sum(fn ($attendance) => $attendance->classSession->durationHours()));
    }
}
