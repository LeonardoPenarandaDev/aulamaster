<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRecoverySettingRequest;
use App\Models\RecoverySetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecoverySettingController extends Controller
{
    /**
     * Show the singleton recovery configuration form.
     */
    public function edit(): Response
    {
        return Inertia::render('RecoverySettings/Edit', [
            'settings' => RecoverySetting::current(),
        ]);
    }

    /**
     * Update the recovery configuration (sección 22 del plan).
     */
    public function update(UpdateRecoverySettingRequest $request): RedirectResponse
    {
        RecoverySetting::current()->update($request->validated());

        return to_route('recovery-settings.edit')->with('success', 'Configuración de recuperaciones actualizada.');
    }
}
