<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Grant all abilities to administrators. There is deliberately no
     * "update" or "delete" ability defined anywhere in this policy, and no
     * corresponding routes exist: a confirmed attendance record can never be
     * edited or removed directly (sección 16 del plan), even by an admin.
     * Corrections go through AttendanceCorrectionController instead.
     */
    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        // Sin docente no se toma asistencia, ni siquiera el admin (parte 11
        // del plan de mejoras): la asistencia queda a nombre del docente.
        if ($ability === 'create' && ($arguments[1] ?? null) instanceof ClassSession && ! $arguments[1]->teacher_id) {
            return false;
        }

        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $attendance->enrollment->student->user_id === $user->id
            || $attendance->teacher->user_id === $user->id;
    }

    /**
     * A teacher may only take attendance for a class session they teach,
     * and only on the day the class actually happens (Fase 15 del
     * checklist) — así no se puede marcar asistencia retroactiva ni
     * adelantada. El admin conserva su acceso total vía before().
     */
    public function create(User $user, ClassSession $classSession): bool
    {
        return $classSession->teacher?->user_id === $user->id && $classSession->date->isToday();
    }

    /**
     * The audited correction process from sección 16. Admin-only for now.
     */
    public function correct(User $user, Attendance $attendance): bool
    {
        return false;
    }
}
