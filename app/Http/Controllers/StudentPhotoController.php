<?php

namespace App\Http\Controllers;

use App\Actions\Students\StoreStudentPhoto;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto de perfil del estudiante (disco privado).
 */
class StudentPhotoController extends Controller
{
    /**
     * Show the photo to the staff, teachers and the student themselves.
     */
    public function show(Student $student): StreamedResponse
    {
        Gate::authorize('viewPhoto', $student);
        abort_unless($student->photo_path && Storage::disk('local')->exists($student->photo_path), 404);

        return Storage::disk('local')->response($student->photo_path, 'foto.jpg', [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ], 'inline');
    }

    /**
     * Replace the photo from the student's record.
     */
    public function update(Request $request, Student $student, StoreStudentPhoto $storePhoto): RedirectResponse
    {
        Gate::authorize('update', $student);

        $request->validate(['photo' => Student::photoRules()], Student::photoMessages());

        $storePhoto->handle($student, $request->file('photo'));

        return back()->with('success', 'Foto actualizada.');
    }

    public function destroy(Student $student, StoreStudentPhoto $storePhoto): RedirectResponse
    {
        Gate::authorize('update', $student);

        $storePhoto->delete($student);

        return back()->with('success', 'Foto eliminada.');
    }
}
