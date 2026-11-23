<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Kit de inicio entregado', 'Carné entregado', 'Prueba de nivel presentada']),
            'description' => null,
            'automatic_check' => null,
            'position' => 1,
            'is_active' => true,
        ];
    }

    public function automatic(string $check): static
    {
        return $this->state(['automatic_check' => $check]);
    }
}
