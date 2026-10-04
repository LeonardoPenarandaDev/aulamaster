<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInstitutionSettingRequest;
use App\Models\InstitutionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionSettingController extends Controller
{
    /**
     * Show the singleton institution configuration form (logo + datos usados
     * en los certificados, Fase 16 del checklist).
     */
    public function edit(): Response
    {
        $settings = InstitutionSetting::current();

        return Inertia::render('InstitutionSettings/Edit', [
            'settings' => [
                ...$settings->toArray(),
                'logo_url' => $settings->logoUrl(),
            ],
        ]);
    }

    /**
     * Update the institution configuration. The logo file (if any) is
     * stored separately from the rest of the fields.
     */
    public function update(UpdateInstitutionSettingRequest $request): RedirectResponse
    {
        $settings = InstitutionSetting::current();
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('institution', 'public');
        }

        $settings->update($data);
        InstitutionSetting::forgetBranding();

        return to_route('institution-settings.edit')->with('success', 'Configuración institucional actualizada.');
    }
}
