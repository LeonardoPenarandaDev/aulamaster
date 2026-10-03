<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentMaterialController extends Controller
{
    /**
     * Material de repaso de las clases a las que el estudiante asistió. Solo
     * se incluyen las clases cuya asistencia efectiva (teniendo en cuenta
     * correcciones) es "presente"; ausentes y excusados no ven el material.
     */
    public function index(Request $request): Response
    {
        $student = $request->user()->student;

        $sessions = $student
            ? Attendance::query()
                ->whereHas('enrollment', fn ($query) => $query->where('student_id', $student->id))
                ->whereHas('classSession.materials')
                ->with([
                    'corrections',
                    'classSession.level.course:id,name',
                    'classSession.materials' => fn ($query) => $query->latest(),
                ])
                ->orderByDesc('class_date')
                ->get()
                ->filter(fn (Attendance $attendance) => $attendance->effective_status === 'presente')
                ->map(fn (Attendance $attendance) => [
                    'id' => $attendance->classSession->id,
                    'level' => "{$attendance->classSession->level->course->name} {$attendance->classSession->level->name}",
                    'date' => $attendance->classSession->date->toDateString(),
                    'start_time' => substr($attendance->classSession->start_time, 0, 5),
                    'end_time' => substr($attendance->classSession->end_time, 0, 5),
                    'materials' => $attendance->classSession->materials
                        ->map->toPortalArray()
                        ->values(),
                ])
                ->values()
            : collect();

        return Inertia::render('StudentMaterials/Index', [
            'sessions' => $sessions,
        ]);
    }
}
