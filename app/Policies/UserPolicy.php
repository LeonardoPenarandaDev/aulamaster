<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestión de los usuarios del personal (admin, coordinador, cajero):
 * solo el administrador puede verlos, crearlos o modificarlos.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model. Solo se editan
     * cuentas del personal; las de profesores y estudiantes se gestionan
     * desde sus propias fichas.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin') && $model->hasRole(User::STAFF_ROLES);
    }
}
