<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla a la que se envía a quien inicia sesión con una contraseña
 * temporal (users.must_change_password): no puede usar la aplicación hasta
 * elegir una propia.
 */
class ForcedPasswordChangeController extends Controller
{
    /**
     * Show the change password form.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return to_route('dashboard');
        }

        return Inertia::render('Auth/ChangePassword');
    }

    /**
     * Save the user's new password and lift the restriction.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (Hash::check($value, $user->password)) {
                        $fail('La nueva contraseña debe ser diferente de la temporal.');
                    }
                },
            ],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return to_route('dashboard')->with('success', 'Contraseña actualizada. ¡Bienvenido!');
    }
}
