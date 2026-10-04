<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppearanceRequest;
use App\Models\InstitutionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Apariencia": colores de la aplicación y estilo del menú lateral. Solo
 * el administrador.
 */
class AppearanceController extends Controller
{
    public function edit(): Response
    {
        Gate::authorize('update-appearance');

        $settings = InstitutionSetting::current();

        return Inertia::render('Appearance/Edit', [
            'appearance' => [
                'primary_color' => $settings->primary_color ?: InstitutionSetting::DEFAULT_PRIMARY_COLOR,
                'accent_color' => $settings->accent_color ?: InstitutionSetting::DEFAULT_ACCENT_COLOR,
                'sidebar_style' => $settings->sidebar_style ?: 'color',
            ],
            'sidebarStyles' => InstitutionSetting::SIDEBAR_STYLES,
            'defaults' => [
                'primary_color' => InstitutionSetting::DEFAULT_PRIMARY_COLOR,
                'accent_color' => InstitutionSetting::DEFAULT_ACCENT_COLOR,
            ],
        ]);
    }

    public function update(UpdateAppearanceRequest $request): RedirectResponse
    {
        InstitutionSetting::current()->update([
            ...$request->validated(),
            'primary_color' => strtoupper($request->validated('primary_color')),
            'accent_color' => strtoupper($request->validated('accent_color')),
        ]);
        InstitutionSetting::forgetBranding();

        return to_route('appearance.edit')->with('success', 'Apariencia actualizada. Los colores ya se ven en toda la aplicación.');
    }
}
