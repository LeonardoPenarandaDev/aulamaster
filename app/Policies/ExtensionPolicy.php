<?php

namespace App\Policies;

use App\Models\Extension;
use App\Models\User;

class ExtensionPolicy
{
    /**
     * Grant all abilities to administrators.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Extension $extension): bool
    {
        return $extension->enrollment->student->user_id === $user->id;
    }
}
