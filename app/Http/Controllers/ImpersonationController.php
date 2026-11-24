<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Http\Middleware\ImpersonateStudentInTab;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * El administrador abre, en una pestaña nueva, la plataforma tal como la ve
 * un estudiante, sin conocer su contraseña y sin cerrar su propia sesión:
 * la pestaña lleva una llave temporal (ImpersonateStudentInTab) y las demás
 * pestañas siguen siendo del administrador. Queda en la auditoría.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, Student $student, RecordAuditEvent $recordAudit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $studentUser = $student->user;

        if (! $studentUser) {
            return back()->with('error', 'Este estudiante todavía no tiene acceso a la plataforma. Crea su acceso primero.');
        }

        if (! $studentUser->is_active) {
            return back()->with('error', 'La cuenta de este estudiante está inactiva.');
        }

        $admin = $request->user();

        $recordAudit->handle('ingreso_como_estudiante', 'usuarios', $studentUser, "{$admin->name} entró a la cuenta de {$student->name}");

        return redirect()->route('dashboard', [
            ImpersonateStudentInTab::QUERY_KEY => ImpersonateStudentInTab::tokenFor($admin, $studentUser),
        ]);
    }
}
