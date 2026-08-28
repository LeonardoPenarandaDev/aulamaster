<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Extension;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extension>
 */
class ExtensionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'previous_end_date' => now()->toDateString(),
            'new_end_date' => now()->addWeek()->toDateString(),
            'reason' => 'Recuperación de evaluación',
            'extended_by_id' => User::factory(),
        ];
    }
}
