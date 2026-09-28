<?php

namespace App\Policies;

use App\Models\ClassMaterial;
use App\Models\ClassSession;
use App\Models\User;

class ClassMaterialPolicy
{
    /**
     * Grant all abilities to administrators.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Solo el profesor que dicta la clase puede ver y compartir su material
     * (en cualquier fecha, para poder subirlo después de la clase).
     */
    public function create(User $user, ClassSession $classSession): bool
    {
        return $classSession->teacher?->user_id === $user->id;
    }

    public function delete(User $user, ClassMaterial $classMaterial): bool
    {
        return $this->create($user, $classMaterial->classSession);
    }
}
