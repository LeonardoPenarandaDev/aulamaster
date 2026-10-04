<?php

namespace Tests\Feature;

use App\Models\InstitutionSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Sistema → Apariencia: el admin configura los colores de la aplicación y
 * el estilo del menú lateral.
 */
class AppearanceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_changes_colors_and_sidebar_style(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('appearance.edit'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Appearance/Edit')
                ->where('appearance.sidebar_style', 'color')
                ->where('appearance.primary_color', InstitutionSetting::DEFAULT_PRIMARY_COLOR)
            );

        $this->actingAs($admin)
            ->put(route('appearance.update'), ['primary_color' => '#047857', 'accent_color' => '#f59e0b', 'sidebar_style' => 'oscuro'])
            ->assertRedirect(route('appearance.edit'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->get('/dashboard')
            ->assertSee('--c-primary-600: 4 120 87;', false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('institution.sidebar_style', 'oscuro')
                ->where('institution.accent_color', '#F59E0B')
            );
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->adminUser())
            ->put(route('appearance.update'), ['primary_color' => 'azul', 'accent_color' => '#F97316', 'sidebar_style' => 'arcoiris'])
            ->assertSessionHasErrors(['primary_color', 'sidebar_style']);
    }

    public function test_only_the_admin_can_change_the_appearance(): void
    {
        foreach ([$this->coordinadorUser(), $this->secretariaUser(), $this->cajeroUser()] as $user) {
            $this->actingAs($user)->get(route('appearance.edit'))->assertForbidden();
        }
    }
}
