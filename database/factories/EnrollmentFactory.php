<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $level = Level::factory()->create();

        return [
            'student_id' => Student::factory(),
            'level_id' => $level->id,
            'enrolled_at' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'estimated_end_date' => now()->addMonths($level->duration_months ?? 4)->toDateString(),
            'status' => 'activa',
            'required_hours' => $level->required_hours,
            'accumulated_hours' => 0,
            'base_price' => $level->price,
            'final_price' => $level->price,
        ];
    }
}
