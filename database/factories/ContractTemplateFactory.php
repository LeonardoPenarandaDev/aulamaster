<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContractTemplate>
 */
class ContractTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Contrato '.fake()->unique()->words(2, true);

        return [
            'code' => Str::slug($name),
            'version' => 1,
            'name' => $name,
            'type' => 'matricula',
            'body' => "Yo, {{alumno.nombre}}, identificado con {{alumno.documento}}, me matriculo en {{curso}} {{nivel}} por un valor de {{precio_final}}.\n\nFirmado en {{institucion.nombre}} el {{fecha}}.",
            'status' => 'borrador',
            'acceptance_mode' => 'obligatorio',
            'scope' => 'matricula',
            'requires_guardian' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publicado',
            'published_at' => now(),
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'acceptance_mode' => 'opcional',
        ]);
    }

    public function perStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'alumno',
        ]);
    }
}
