<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PortalAccessController extends Controller
{
    /**
     * Create a login account for a student and link it, so they can reach
     * their own dashboard (sección 37 del plan). No hay sistema de envío de
     * correo todavía (Fase 13), así que la contraseña temporal se muestra
     * una sola vez al admin para que se la comparta manualmente.
     */
    public function storeForStudent(Student $student): RedirectResponse
    {
        abort_if($student->user_id, 409, 'Este estudiante ya tiene acceso al portal.');
        abort_unless($student->email, 422, 'El estudiante necesita un correo registrado antes de crear su acceso.');

        $password = Str::password(12);

        $user = User::create([
            'name' => $student->name,
            'email' => $student->email,
            'password' => $password,
        ]);
        // El estudiante no cambia su contraseña: la administra el instituto.
        $user->assignRole('estudiante');
        $student->update(['user_id' => $user->id]);

        return back()->with('success', "Acceso creado. Usuario: {$user->email} · Contraseña temporal: {$password}");
    }

    /**
     * Create the login account for a teacher (sección 36 del plan): el
     * usuario es su correo y la contraseña inicial su número de documento.
     */
    public function storeForTeacher(Teacher $teacher): RedirectResponse
    {
        abort_if($teacher->user_id, 409, 'Este profesor ya tiene acceso al portal.');
        abort_unless($teacher->email && $teacher->document, 422, 'El profesor necesita correo y documento registrados antes de crear su acceso.');

        if (User::query()->where('email', $teacher->email)->exists()) {
            return back()->with('error', "Ya existe una cuenta con el correo {$teacher->email}.");
        }

        $user = User::create([
            'name' => $teacher->name,
            'email' => $teacher->email,
            'password' => $teacher->document,
        ]);
        $user->assignRole('profesor');
        $teacher->update(['user_id' => $user->id]);

        return back()->with('success', "Acceso creado. Usuario: {$user->email} · Contraseña: su número de documento.");
    }
}
