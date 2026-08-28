<?php

namespace App\Policies;

use App\Models\Level;
use App\Models\User;

class LevelPolicy
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

    public function view(User $user, Level $level): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Level $level): bool
    {
        return false;
    }

    public function delete(User $user, Level $level): bool
    {
        return false;
    }

    public function restore(User $user, Level $level): bool
    {
        return false;
    }

    public function forceDelete(User $user, Level $level): bool
    {
        return false;
    }
}
