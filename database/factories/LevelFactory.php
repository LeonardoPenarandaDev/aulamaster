<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']),
            'color' => fake()->randomElement(['#DBEAFE', '#DCFCE7', '#FEF9C3', '#FCE7F3', '#EDE9FE', '#FFEDD5']),
            'code' => fake()->unique()->bothify('NIV-###'),
            'duration_months' => 4,
            'weekly_hours' => 8,
            'monthly_hours' => 32,
            'required_hours' => 128,
            'minimum_grade' => 70,
            'price' => 300000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 'activo',
        ];
    }
}
