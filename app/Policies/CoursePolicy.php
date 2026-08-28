<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Grant all abilities to administrators and to the scheduling
     * coordinator (Fase 15 del checklist: solo programa/organiza el
     * calendario académico, sin ver el resto del sistema).
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'coordinador']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Course $course): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Course $course): bool
    {
        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        return false;
    }

    public function restore(User $user, Course $course): bool
    {
        return false;
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return false;
    }
}
