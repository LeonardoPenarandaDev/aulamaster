<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Grant all abilities to administrators and to the billing clerk
     * (Fase 15 del checklist). There is deliberately no "delete"
     * ability/route: los pagos no deben eliminarse físicamente (sección 33
     * del plan) — un pago erróneo se anula (status = anulado), nunca se
     * borra.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'cajero']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $payment->student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }
}
