<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\GetWeeklyHoursSummary;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StudentScheduleController extends Controller
{
    /**
     * Horarios disponibles para el estudiante: todas las clases de su nivel
     * en la semana actual y la siguiente, tal como las programa el admin en
     * el calendario. El estudiante puede asistir a cualquier grupo de su
     * nivel para cumplir su intensidad semanal o recuperar horas. No se
     * muestra el profesor: la institución lo asigna y el estudiante no
     * debe elegir clases según quién la dicte.
     */
    public function index(Request $request, GetWeeklyHoursSummary $getWeeklyHoursSummary): Response
    {
        $student = $request->user()->student;

        $enrollment = $student
            ? Enrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
                ->with('level.course')
                ->latest('enrolled_at')
                ->first()
            : null;

        if (! $enrollment) {
            return Inertia::render('StudentSchedule/Index', ['enrollment' => null, 'sessions' => []]);
        }

        $from = today();
        $until = today()->startOfWeek(Carbon::MONDAY)->addWeek()->endOfWeek(Carbon::SUNDAY);

        $attendanceBySession = Attendance::query()
            ->where('enrollment_id', $enrollment->id)
            ->with('corrections')
            ->get()
            ->mapWithKeys(fn (Attendance $attendance) => [$attendance->class_session_id => $attendance->effective_status]);

        $sessions = ClassSession::query()
            ->where('level_id', $enrollment->level_id)
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->with('classroom:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'date' => $session->date->toDateString(),
                'week_start' => $session->date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'hours' => $session->durationHours(),
                'classroom' => $session->classroom->name,
                'status' => $session->status,
                'notes' => $session->notes,
                'modality' => $session->modality,
                'meeting_url' => $session->meeting_url,
                'attendance_status' => $attendanceBySession[$session->id] ?? null,
            ]);

        $currentWeek = collect($getWeeklyHoursSummary->handle($enrollment, 1))->first();

        return Inertia::render('StudentSchedule/Index', [
            'enrollment' => [
                'course' => $enrollment->level->course->name,
                'level' => $enrollment->level->name,
                'weekly_hours' => (float) $enrollment->weekly_hours,
            ],
            'current_week' => $currentWeek,
            'catch_up' => $getWeeklyHoursSummary->catchUp($enrollment),
            'sessions' => $sessions,
        ]);
    }
}
