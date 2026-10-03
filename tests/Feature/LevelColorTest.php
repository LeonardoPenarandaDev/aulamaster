<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 4 del plan de mejoras: cada nivel tiene un color (#RRGGBB) que se
 * usa como fondo del portal de sus estudiantes.
 */
class LevelColorTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function levelData(array $overrides = []): array
    {
        return array_merge([
            'course_id' => Course::factory()->create()->id,
            'name' => 'A1',
            'code' => 'A1-COLOR',
            'required_hours' => 100,
            'minimum_grade' => 70,
            'price' => 300000,
            'status' => 'activo',
        ], $overrides);
    }

    public function test_level_is_saved_with_the_chosen_color(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('levels.store'), $this->levelData(['color' => '#FCE7F3']))
            ->assertSessionHasNoErrors();

        $this->assertSame('#FCE7F3', Level::query()->where('code', 'A1-COLOR')->value('color'));
    }

    public function test_level_without_color_uses_the_default_light_blue(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('levels.store'), $this->levelData(['color' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Level::DEFAULT_COLOR, Level::query()->where('code', 'A1-COLOR')->value('color'));
    }

    public function test_color_must_be_a_hex_value(): void
    {
        $admin = $this->adminUser();

        foreach (['red', '#FFF', '#GGGGGG', 'DBEAFE'] as $invalidColor) {
            $this->actingAs($admin)
                ->post(route('levels.store'), $this->levelData(['color' => $invalidColor]))
                ->assertSessionHasErrors('color');
        }
    }

    public function test_coordinador_can_change_the_color(): void
    {
        $level = Level::factory()->create(['color' => '#DBEAFE']);

        $this->actingAs($this->coordinadorUser())
            ->put(route('levels.update', $level), $this->levelData([
                'course_id' => $level->course_id,
                'code' => $level->code,
                'color' => '#dcfce7',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('#dcfce7', $level->fresh()->color);
    }
}
