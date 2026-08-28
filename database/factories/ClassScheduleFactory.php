<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
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
            'days_of_week' => [1, 3, 5],
            'start_time' => '14:00',
            'end_time' => '15:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 'activo',
        ];
    }
}
