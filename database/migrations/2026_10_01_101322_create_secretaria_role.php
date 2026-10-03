<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * El servidor solo ejecuta `migrate` al actualizar (ver update.md), así que
 * el rol nuevo se crea aquí en vez de depender de volver a correr RoleSeeder.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::findOrCreate('secretaria', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::query()->where('name', 'secretaria')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
