<?php

namespace App\Actions\Dashboard;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Teacher;

class GetTeacherDashboardData
{
    /**
     * Datos del dashboard del profesor (sección 36 del plan): sus cursos,
     * la clase de hoy, cuántos estudiantes tiene y el resumen de asistencia
     * de esa clase.
     *
     * @return array<string, mixed>
     */
    public function handle(Teacher $teacher): array
    {
        $myLevels = ClassSession::query()
            ->where('teacher_id', $teacher->id)
            ->with('level.course:id,name')
            ->get()
            ->pluck('level')
            ->unique('id')
            ->map(fn ($level) => "{$level->course->name} {$level->name}")
            ->values();

        $todaySessions = ClassSession::query()
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', today())
            ->with(['level.course:id,name', 'classroom:id,name'])
            ->orderBy('start_time')
            ->get()
            ->map(function (ClassSession $session) {
                $enrolledCount = Enrollment::where('level_id', $session->level_id)
                    ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
                    ->count();

                $attendance = Attendance::where('class_session_id', $session->id)->get();

                return [
                    'id' => $session->id,
                    'level' => "{$session->level->course->name} {$session->level->name}",
                    'classroom' => $session->classroom->name,
                    'start_time' => substr($session->start_time, 0, 5),
                    'end_time' => substr($session->end_time, 0, 5),
                    'status' => $session->status,
                    'enrolled_count' => $enrolledCount,
                    'attendance_taken' => $attendance->count() > 0,
                    'present_count' => $attendance->where('status', 'presente')->count(),
                    'absent_count' => $attendance->where('status', 'ausente')->count(),
                    'materials_count' => $session->materials()->count(),
                    'modality' => $session->modality,
                    'meeting_url' => $session->meeting_url,
                ];
            });

        // Clases de los últimos 7 días, para que el profesor pueda compartir
        // material de repaso después de dictarlas.
        $recentSessions = ClassSession::query()
            ->where('teacher_id', $teacher->id)
            ->whereBetween('date', [today()->subDays(7)->toDateString(), today()->subDay()->toDateString()])
            ->where('status', '!=', 'cancelada')
            ->with('level.course:id,name')
            ->withCount('materials')
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'level' => "{$session->level->course->name} {$session->level->name}",
                'date' => $session->date->toDateString(),
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'materials_count' => $session->materials_count,
            ]);

        return [
            'my_levels' => $myLevels,
            'today_sessions' => $todaySessions,
            'recent_sessions' => $recentSessions,
        ];
    }
}
