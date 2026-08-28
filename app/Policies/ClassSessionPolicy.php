<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy
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

    public function view(User $user, ClassSession $classSession): bool
    {
        return $classSession->teacher->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ClassSession $classSession): bool
    {
        return false;
    }

    public function delete(User $user, ClassSession $classSession): bool
    {
        return false;
    }

    public function restore(User $user, ClassSession $classSession): bool
    {
        return false;
    }

    public function forceDelete(User $user, ClassSession $classSession): bool
    {
        return false;
    }
}
