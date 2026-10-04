<?php

namespace Tests\Feature;

use App\Models\InstitutionSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 1 del plan de mejoras: el nombre y el logo de la institución se
 * muestran en toda la aplicación (pestaña, barra de navegación y login).
 */
class InstitutionBrandingTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_login_page_shows_the_institution_name_and_default_favicon(): void
    {
        InstitutionSetting::current()->update(['name' => 'Instituto Horizonte']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<title inertia>Instituto Horizonte</title>', false)
            ->assertSee('<link rel="icon" href="/favicon.ico">', false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('institution.name', 'Instituto Horizonte')
                ->where('institution.logo_url', null)
            );
    }

    public function test_updating_the_settings_refreshes_the_cached_name_and_logo(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('institution.name', 'AulaMaster'));

        $this->actingAs($admin)
            ->post(route('institution-settings.update'), [
                'name' => 'Academia Nueva',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertSessionHasNoErrors();

        $logoUrl = InstitutionSetting::current()->logoUrl();

        $this->actingAs($admin)->get('/dashboard')
            ->assertSee('<link rel="icon" href="'.$logoUrl.'">', false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('institution.name', 'Academia Nueva')
                ->where('institution.logo_url', $logoUrl)
            );
    }
}
