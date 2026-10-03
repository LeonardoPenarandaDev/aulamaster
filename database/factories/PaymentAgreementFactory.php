<?php

namespace Database\Factories;

use App\Models\PaymentAgreement;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAgreement>
 */
class PaymentAgreementFactory extends Factory
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
            'agreed_until' => now()->addDays(15)->toDateString(),
            'notes' => 'Paga el saldo en la segunda quincena.',
        ];
    }
}
