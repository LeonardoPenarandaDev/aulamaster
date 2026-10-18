<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\BookClassSession;
use App\Models\ClassSession;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * El estudiante se programa (o cancela su cupo) en una clase desde "Mis
 * horarios".
 */
class ClassBookingController extends Controller
{
    public function store(Request $request, ClassSession $classSession, BookClassSession $bookClassSession): RedirectResponse
    {
        $bookClassSession->handle($this->activeEnrollment($request), $classSession);

        return back()->with('success', 'Quedaste programado en la clase.');
    }

    public function destroy(Request $request, ClassSession $classSession, BookClassSession $bookClassSession): RedirectResponse
    {
        $bookClassSession->cancel($this->activeEnrollment($request), $classSession);

        return back()->with('success', 'Cancelaste tu cupo en la clase.');
    }

    private function activeEnrollment(Request $request): Enrollment
    {
        $student = $request->user()->student;

        abort_unless($student, 403);

        return Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->latest('enrolled_at')
            ->firstOr(fn () => abort(403));
    }
}
