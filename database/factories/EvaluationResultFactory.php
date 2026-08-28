<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationResult>
 */
class EvaluationResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = fake()->numberBetween(50, 100);

        return [
            'evaluation_id' => Evaluation::factory(),
            'enrollment_id' => Enrollment::factory(),
            'teacher_id' => Teacher::factory(),
            'attempt_number' => 1,
            'is_recovery' => false,
            'evaluated_at' => now()->toDateString(),
            'grade' => $grade,
            'result' => $grade >= 70 ? 'aprobado' : 'reprobado',
            'registered_by_id' => User::factory(),
        ];
    }
}
