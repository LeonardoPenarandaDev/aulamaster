<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Pequeño helper para pruebas: crea los roles de Spatie (que RefreshDatabase
 * no siembra por sí solo) y usuarios ya asignados a un rol.
 */
trait InteractsWithRoles
{
    protected function ensureRolesExist(): void
    {
        foreach (['admin', 'profesor', 'estudiante', 'coordinador', 'cajero', 'secretaria'] as $role) {
            Role::findOrCreate($role);
        }
    }

    protected function adminUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function teacherUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('profesor');

        return $user;
    }

    protected function studentUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('estudiante');

        return $user;
    }

    protected function coordinadorUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('coordinador');

        return $user;
    }

    protected function cajeroUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('cajero');

        return $user;
    }

    protected function secretariaUser(): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create();
        $user->assignRole('secretaria');

        return $user;
    }
}
