<?php

namespace Database\Factories;

use App\Models\ClassMaterial;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassMaterial>
 */
class ClassMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'title' => fake()->sentence(4),
            'url' => fake()->url(),
            'description' => fake()->optional()->sentence(),
            'created_by_id' => User::factory(),
        ];
    }
}
