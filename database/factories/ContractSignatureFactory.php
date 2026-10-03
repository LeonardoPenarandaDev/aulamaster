<?php

namespace Database\Factories;

use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractSignature>
 */
class ContractSignatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = fake()->paragraphs(3, true);

        return [
            'enrollment_id' => Enrollment::factory(),
            'student_id' => fn (array $attributes) => Enrollment::find($attributes['enrollment_id'])->student_id,
            'contract_template_id' => ContractTemplate::factory()->published(),
            'template_version' => 1,
            'status' => 'pendiente',
            'rendered_body' => $body,
            'content_hash' => hash('sha256', $body),
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'firmado',
            'decision' => 'acepta',
            'signing_method' => 'oficina',
            'signer_name' => fake()->name(),
            'signer_role' => 'alumno',
            'signed_at' => now(),
        ]);
    }
}
