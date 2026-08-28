<?php

namespace App\Actions\Attendance;

use App\Models\Enrollment;

class RecalculateEnrollmentHours
{
    /**
     * Recompute accumulated_hours from scratch based on every attendance
     * record whose effective status is "presente" (sección 17 del plan: las
     * horas de una asistencia ausente nunca se contabilizan; una excusada
     * tampoco, porque el estudiante no estuvo presente en la clase).
     *
     * Recalculating from scratch (instead of incrementing/decrementing on
     * every event) keeps this correct even when a correction retroactively
     * changes an attendance's effective status.
     */
    public function handle(Enrollment $enrollment): void
    {
        $hours = $enrollment->attendances()
            ->with(['classSession', 'corrections'])
            ->get()
            ->filter(fn ($attendance) => $attendance->effective_status === 'presente')
            ->sum(fn ($attendance) => $attendance->classSession->durationHours());

        $enrollment->update(['accumulated_hours' => $hours]);
    }
}
