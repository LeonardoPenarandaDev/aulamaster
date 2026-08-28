<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
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
            'teacher_id' => Teacher::factory(),
            'classroom_id' => Classroom::factory(),
            'date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'programada',
        ];
    }
}
