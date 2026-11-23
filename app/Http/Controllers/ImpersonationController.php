<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * El administrador entra a la plataforma como un estudiante, sin conocer
 * su contraseña, para ver exactamente lo que el estudiante ve. Queda en la
 * auditoría y en cualquier momento vuelve a su cuenta.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function store(Request $request, Student $student, RecordAuditEvent $recordAudit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_if($request->session()->has(self::SESSION_KEY), 409, 'Ya estás dentro de la cuenta de otro estudiante.');

        $studentUser = $student->user;

        if (! $studentUser) {
            return back()->with('error', 'Este estudiante todavía no tiene acceso a la plataforma. Crea su acceso primero.');
        }

        if (! $studentUser->is_active) {
            return back()->with('error', 'La cuenta de este estudiante está inactiva.');
        }

        $admin = $request->user();

        $recordAudit->handle('ingreso_como_estudiante', 'usuarios', $studentUser, "{$admin->name} entró a la cuenta de {$student->name}");

        Auth::login($studentUser);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->id);

        return to_route('dashboard')->with('success', "Estás viendo la plataforma como {$student->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull(self::SESSION_KEY);
        $admin = $adminId ? User::find($adminId) : null;

        abort_unless($admin?->hasRole('admin'), 403);

        $studentId = $request->user()?->student?->id;

        Auth::login($admin);
        $request->session()->regenerate();

        return ($studentId ? to_route('students.edit', $studentId) : to_route('dashboard'))
            ->with('success', 'Volviste a tu cuenta.');
    }
}
