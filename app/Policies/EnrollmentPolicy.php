<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Grant all abilities to administrators and to the billing clerk
     * (Fase 15 del checklist: matricula estudiantes y gestiona pagos, sin
     * acceso operativo/académico). La secretaria también matricula, pero
     * sin los demás permisos (parte 3 del plan de mejoras).
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole(['admin', 'cajero']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('secretaria');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('secretaria');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('secretaria');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }

    public function restore(User $user, Enrollment $enrollment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }
}
