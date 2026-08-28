<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\RecalculateEnrollmentHours;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;

class AttendanceCorrectionController extends Controller
{
    /**
     * Store an audited correction for a confirmed attendance record. The
     * original attendance row is never modified (sección 16 del plan).
     */
    public function store(StoreAttendanceCorrectionRequest $request, Attendance $attendance, RecalculateEnrollmentHours $recalculate): RedirectResponse
    {
        $attendance->corrections()->create([
            'previous_status' => $attendance->effective_status,
            'new_status' => $request->validated('new_status'),
            'reason' => $request->validated('reason'),
            'corrected_by_id' => $request->user()->id,
        ]);

        $recalculate->handle($attendance->enrollment);

        return back()->with('success', 'Corrección registrada correctamente.');
    }
}
