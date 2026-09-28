<?php

namespace App\Actions\Scheduling;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;

class CheckClassSessionConflicts
{
    /**
     * Return validation error messages for any scheduling conflict on the
     * given date/time slot, keyed by the field that should show the error.
     * An empty array means the slot is free. Virtual classes don't occupy a
     * classroom, so they neither cause nor suffer classroom conflicts; the
     * teacher still can't be in two classes at once.
     *
     * @return array<string, string>
     */
    public function handle(
        int $classroomId,
        int $teacherId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreClassSessionId = null,
        string $modality = 'presencial',
    ): array {
        $errors = [];

        if (Carbon::parse($startTime)->greaterThanOrEqualTo(Carbon::parse($endTime))) {
            $errors['end_time'] = 'La hora de finalización debe ser posterior a la hora de inicio.';

            return $errors;
        }

        $overlapping = fn ($query) => $query
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelada')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreClassSessionId, fn ($query, $id) => $query->where('id', '!=', $id));

        if ($modality === 'presencial'
            && $overlapping(ClassSession::query()->where('classroom_id', $classroomId)->where('modality', 'presencial'))->exists()) {
            $errors['classroom_id'] = 'El aula ya tiene una clase programada que se superpone con este horario.';
        }

        if ($overlapping(ClassSession::query()->where('teacher_id', $teacherId))->exists()) {
            $errors['teacher_id'] = 'El profesor ya tiene una clase programada que se superpone con este horario.';
        }

        return $errors;
    }
}
