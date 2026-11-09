<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\LevelTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LevelTopic>
 */
class LevelTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'class_group' => 'Elementary 1',
            'name' => fake()->randomElement(['Verb to be', 'Present simple', 'Past simple', 'Numbers and colors', 'Daily routines']),
            'position' => 1,
        ];
    }
}
