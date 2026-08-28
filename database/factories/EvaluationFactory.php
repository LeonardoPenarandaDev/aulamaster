<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'name' => fake()->randomElement(['Listening', 'Speaking', 'Reading', 'Writing']),
            'competency' => fake()->word(),
            'minimum_grade' => 70,
            'status' => 'activo',
        ];
    }
}
