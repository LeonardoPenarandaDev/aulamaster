<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestión de los usuarios del personal (admin, coordinador, cajero,
 * secretaria): solo el administrador puede verlos, crearlos o modificarlos.
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

    /**
     * Determine whether the user can open the "Restablecer contraseñas" screen.
     */
    public function viewAnyPasswordResets(User $user): bool
    {
        return $user->hasRole(['admin', 'coordinador']);
    }

    /**
     * Determine whether the user can reset another account's password with
     * a temporary one (parte 2 del plan de mejoras). El admin restablece
     * cualquier cuenta; el coordinador solo las de estudiantes y profesores.
     * Nadie restablece la suya: para eso está "Mi perfil".
     */
    public function resetPassword(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('coordinador')
            && $model->hasRole(['estudiante', 'profesor'])
            && ! $model->hasRole(User::STAFF_ROLES);
    }
}
