<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    /**
     * Grant all abilities to administrators and to the billing clerk
     * (Fase 15 del checklist). There is deliberately no "update"/"delete"
     * ability: la relación de referido debe conservarse (sección 31 del
     * plan), no se edita ni se borra una vez creada.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'cajero']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Referral $referral): bool
    {
        return $referral->referrer->user_id === $user->id || $referral->referred->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }
}
