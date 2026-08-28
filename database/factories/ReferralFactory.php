<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referrer_student_id' => Student::factory(),
            'referred_student_id' => Student::factory(),
            'referrer_discount' => 50000,
            'referred_discount' => 25000,
        ];
    }
}
