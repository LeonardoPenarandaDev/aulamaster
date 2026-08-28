<?php

namespace Database\Seeders;

use App\Models\RecoverySetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        RecoverySetting::current();

        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@instituto.test',
        ]);

        $admin->assignRole('admin');
    }
}
