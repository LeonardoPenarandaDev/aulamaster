<?php

namespace Database\Factories;

use App\Models\PaymentFollowUp;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentFollowUp>
 */
class PaymentFollowUpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'channel' => 'llamada',
            'result' => 'contactado',
            'note' => fake()->sentence(),
            'contacted_at' => now(),
        ];
    }
}
