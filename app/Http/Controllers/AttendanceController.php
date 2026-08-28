<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\RecalculateEnrollmentHours;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource (attendance report/history).
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        return Inertia::render('Attendance/Index', [
            'attendances' => Attendance::query()
                ->with(['enrollment.student:id,name,code', 'classSession.level.course:id,name', 'teacher:id,name', 'corrections'])
                ->when(request('student_id'), fn ($query, $id) => $query->whereHas('enrollment', fn ($q) => $q->where('student_id', $id)))
                ->when(request('level_id'), fn ($query, $id) => $query->whereHas('classSession', fn ($q) => $q->where('level_id', $id)))
                ->when(request('date'), fn ($query, $date) => $query->whereDate('class_date', $date))
                ->orderByDesc('class_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => request()->only('student_id', 'level_id', 'date'),
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the roster form to take attendance for a given class session.
     */
    public function create(ClassSession $classSession): Response
    {
        Gate::authorize('create', [Attendance::class, $classSession]);

        $classSession->load(['level.course', 'teacher', 'classroom']);

        $roster = Enrollment::query()
            ->with('student:id,name,code')
            ->where('level_id', $classSession->level_id)
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->orderBy('id')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'enrollment_id' => $enrollment->id,
                'student' => $enrollment->student,
            ]);

        $existing = Attendance::query()
            ->where('class_session_id', $classSession->id)
            ->pluck('status', 'enrollment_id');

        return Inertia::render('Attendance/Create', [
            'classSession' => $classSession,
            'roster' => $roster,
            'existing' => $existing,
        ]);
    }

    /**
     * Store the attendance records submitted for a class session.
     */
    public function store(StoreAttendanceRequest $request, ClassSession $classSession, RecalculateEnrollmentHours $recalculate): RedirectResponse
    {
        $alreadyRecorded = Attendance::query()
            ->where('class_session_id', $classSession->id)
            ->pluck('enrollment_id')
            ->all();

        $created = 0;

        foreach ($request->validated('records') as $record) {
            if (in_array((int) $record['enrollment_id'], $alreadyRecorded, strict: true)) {
                continue;
            }

            $attendance = Attendance::create([
                'class_session_id' => $classSession->id,
                'enrollment_id' => $record['enrollment_id'],
                'teacher_id' => $classSession->teacher_id,
                'class_date' => $classSession->date,
                'status' => $record['status'],
                'registered_by_id' => $request->user()->id,
            ]);

            $recalculate->handle($attendance->enrollment);
            $created++;
        }

        return to_route('attendance.create', $classSession)->with('success', "Asistencia registrada para {$created} estudiantes.");
    }
}
