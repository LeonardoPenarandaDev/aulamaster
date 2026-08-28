<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Salón '.fake()->unique()->numberBetween(100, 599),
            'code' => fake()->unique()->bothify('AULA-###'),
            'capacity' => fake()->numberBetween(15, 35),
            'location' => fake()->randomElement(['Primer piso', 'Segundo piso', 'Tercer piso']),
            'floor' => fake()->randomElement(['1', '2', '3']),
            'status' => 'disponible',
        ];
    }
}
