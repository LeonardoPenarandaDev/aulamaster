<?php

namespace Database\Factories;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassBooking>
 */
class ClassBookingFactory extends Factory
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
            'enrollment_id' => Enrollment::factory(),
        ];
    }
}
