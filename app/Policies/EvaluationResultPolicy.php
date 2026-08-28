<?php

namespace App\Policies;

use App\Models\EvaluationResult;
use App\Models\User;

class EvaluationResultPolicy
{
    /**
     * Grant all abilities to administrators. There is deliberately no
     * "update"/"delete" ability and no corresponding routes: a confirmed
     * evaluation result cannot be modified directly (Regla 4 del plan).
     * A correction is a new attempt (attempt_number + 1), never an edit.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, EvaluationResult $evaluationResult): bool
    {
        return $evaluationResult->enrollment->student->user_id === $user->id
            || $evaluationResult->teacher->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('profesor');
    }
}
