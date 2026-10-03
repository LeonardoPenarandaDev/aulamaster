<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;

/**
 * Solo el administrador gestiona las plantillas de contrato (parte 6.1 del
 * plan de mejoras).
 */
class ContractTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Solo los borradores se editan; una plantilla publicada se cambia
     * creando una versión nueva.
     */
    public function update(User $user, ContractTemplate $contractTemplate): bool
    {
        return $user->hasRole('admin') && $contractTemplate->isDraft();
    }

    public function delete(User $user, ContractTemplate $contractTemplate): bool
    {
        return $user->hasRole('admin') && $contractTemplate->isDraft();
    }

    public function publish(User $user, ContractTemplate $contractTemplate): bool
    {
        return $user->hasRole('admin') && $contractTemplate->isDraft();
    }

    public function createVersion(User $user, ContractTemplate $contractTemplate): bool
    {
        return $user->hasRole('admin') && ! $contractTemplate->isDraft();
    }

    public function archive(User $user, ContractTemplate $contractTemplate): bool
    {
        return $user->hasRole('admin') && $contractTemplate->status === 'publicado';
    }
}
