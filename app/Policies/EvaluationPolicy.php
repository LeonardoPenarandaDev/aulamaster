<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    /**
     * Grant all abilities to administrators.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function restore(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function forceDelete(User $user, Evaluation $evaluation): bool
    {
        return false;
    }
}
