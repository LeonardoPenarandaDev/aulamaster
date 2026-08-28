<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'concept' => 'Matrícula',
            'base_amount' => 300000,
            'discount_amount' => 0,
            'final_amount' => 300000,
            'status' => 'pendiente',
            'registered_by_id' => User::factory(),
        ];
    }
}
