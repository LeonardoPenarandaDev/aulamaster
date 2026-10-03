<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\GetStudentsWithAbsences;
use App\Actions\Attendance\RecalculateEnrollmentHours;
use App\Actions\Evaluations\EvaluateLevelCompletion;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * Estudiantes que faltaron o no pudieron asistir en un rango de fechas,
     * con sus datos de contacto para que el instituto pueda llamarlos.
     */
    public function absences(Request $request, GetStudentsWithAbsences $getStudentsWithAbsences): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:todos,ausente,excusado'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
        ]);

        $filters = [
            'from' => $filters['from'] ?? today()->subDays(30)->toDateString(),
            'to' => $filters['to'] ?? today()->toDateString(),
            'status' => $filters['status'] ?? 'todos',
            'level_id' => $filters['level_id'] ?? null,
        ];

        return Inertia::render('Attendance/Absences', [
            'students' => $getStudentsWithAbsences->handle(
                Carbon::parse($filters['from']),
                Carbon::parse($filters['to']),
                $filters['status'] === 'todos' ? ['ausente', 'excusado'] : [$filters['status']],
                $filters['level_id'] ? (int) $filters['level_id'] : null,
            ),
            'filters' => $filters,
            'levels' => Level::query()->with('course:id,name')->orderBy('name')->get(['id', 'name', 'course_id']),
        ]);
    }

    /**
     * Show the roster form to take attendance for a given class session.
     */
    public function create(ClassSession $classSession): Response
    {
        Gate::authorize('create', [Attendance::class, $classSession]);

        $classSession->load(['level.course', 'teacher', 'classroom']);

        $enrollments = Enrollment::query()
            ->with('student:id,name,code')
            ->where('level_id', $classSession->level_id)
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->orderBy('id')
            ->get();

        // Al estudiante en mora el docente no le toma asistencia (parte 8 del
        // plan de mejoras); se muestra deshabilitado en la lista.
        $blockedStudentIds = Student::blockedForDebtIds($enrollments->pluck('student_id')->all());

        $roster = $enrollments->map(fn (Enrollment $enrollment) => [
            'enrollment_id' => $enrollment->id,
            'student' => $enrollment->student,
            'is_blocked' => in_array($enrollment->student_id, $blockedStudentIds, true),
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
    public function store(
        StoreAttendanceRequest $request,
        ClassSession $classSession,
        RecalculateEnrollmentHours $recalculate,
        EvaluateLevelCompletion $evaluateCompletion,
    ): RedirectResponse {
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
            $evaluateCompletion->approveIfComplete($attendance->enrollment->fresh());
            $created++;
        }

        return to_route('attendance.create', $classSession)->with('success', "Asistencia registrada para {$created} estudiantes.");
    }
}
