<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'teacher_id' => Teacher::factory(),
            'class_date' => now()->toDateString(),
            'status' => 'presente',
            'registered_by_id' => User::factory(),
        ];
    }
}
