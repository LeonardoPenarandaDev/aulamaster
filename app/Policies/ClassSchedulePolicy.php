<?php

namespace App\Policies;

use App\Models\ClassSchedule;
use App\Models\User;

class ClassSchedulePolicy
{
    /**
     * Grant all abilities to administrators and to the scheduling
     * coordinator (Fase 15 del checklist).
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'coordinador']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, ClassSchedule $classSchedule): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ClassSchedule $classSchedule): bool
    {
        return false;
    }

    public function delete(User $user, ClassSchedule $classSchedule): bool
    {
        return false;
    }

    public function restore(User $user, ClassSchedule $classSchedule): bool
    {
        return false;
    }

    public function forceDelete(User $user, ClassSchedule $classSchedule): bool
    {
        return false;
    }
}
