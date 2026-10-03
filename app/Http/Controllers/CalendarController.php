<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\GetCalendarSessions;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calendario estilo Google Calendar para todos los roles (parte 10 del plan
 * de mejoras). La vista pide las clases del rango visible con una recarga
 * parcial (only: ['sessions']).
 */
class CalendarController extends Controller
{
    public function index(Request $request, GetCalendarSessions $getCalendarSessions): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
            'classroom_id' => ['nullable', 'integer'],
            'teacher_id' => ['nullable', 'integer'],
            'level_id' => ['nullable', 'integer'],
        ]);

        $anchor = Carbon::parse($validated['date'] ?? today());
        $from = Carbon::parse($validated['from'] ?? $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY));
        $to = Carbon::parse($validated['to'] ?? $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY));

        // Nunca más de ~2 meses por petición.
        if ($from->diffInDays($to) > 62) {
            $to = $from->copy()->addDays(62);
        }

        $user = $request->user();
        $isStaff = $user->hasRole(['admin', 'coordinador']);
        $filters = $isStaff ? array_filter($request->only('classroom_id', 'teacher_id', 'level_id')) : [];

        return Inertia::render('Calendar/Index', [
            'sessions' => fn () => $getCalendarSessions->handle($user, $from, $to, $filters),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'initialDate' => $anchor->toDateString(),
            'filters' => $filters,
            'filterOptions' => $isStaff ? [
                'levels' => Level::query()->with('course:id,name')->orderBy('name')->get(['id', 'name', 'course_id', 'color']),
                'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
                'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
            ] : null,
            'canCreate' => $isStaff,
        ]);
    }
}
