<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    /**
     * Grant all abilities to administrators and to the billing clerk
     * (Fase 15 del checklist).
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'cajero']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return false;
    }
}
