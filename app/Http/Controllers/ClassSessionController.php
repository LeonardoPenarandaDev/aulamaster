<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\NotifyClassSessionChange;
use App\Http\Requests\StoreClassSessionRequest;
use App\Http\Requests\UpdateClassSessionMeetingUrlRequest;
use App\Http\Requests\UpdateClassSessionRequest;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Level;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassSessionController extends Controller
{
    /**
     * Display a listing of the resource (the academic calendar/agenda).
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', ClassSession::class);

        $month = request('month')
            ? Carbon::createFromFormat('Y-m', request('month'))->startOfMonth()
            : now()->startOfMonth();
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return Inertia::render('ClassSessions/Index', [
            'classSessions' => ClassSession::query()
                ->with(['level.course:id,name', 'teacher:id,name', 'classroom:id,name'])
                ->when(request('date'), fn ($query, $date) => $query->whereDate('date', $date))
                ->when(request('classroom_id'), fn ($query, $id) => $query->where('classroom_id', $id))
                ->when(request('teacher_id'), fn ($query, $id) => $query->where('teacher_id', $id))
                ->when(request('level_id'), fn ($query, $id) => $query->where('level_id', $id))
                ->orderBy('date')
                ->orderBy('start_time')
                ->paginate(20)
                ->withQueryString(),
            'calendarSessions' => ClassSession::query()
                ->with(['level.course:id,name', 'teacher:id,name', 'classroom:id,name'])
                ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
                ->when(request('classroom_id'), fn ($query, $id) => $query->where('classroom_id', $id))
                ->when(request('teacher_id'), fn ($query, $id) => $query->where('teacher_id', $id))
                ->when(request('level_id'), fn ($query, $id) => $query->where('level_id', $id))
                ->orderBy('start_time')
                ->get(),
            'calendarMonth' => $month->format('Y-m'),
            'calendarGridStart' => $gridStart->toDateString(),
            'calendarGridEnd' => $gridEnd->toDateString(),
            'filters' => request()->only('date', 'classroom_id', 'teacher_id', 'level_id'),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', ClassSession::class);

        return Inertia::render('ClassSessions/Create', [
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Publica (o quita) el enlace de la reunión de una clase virtual.
     */
    public function updateMeetingUrl(UpdateClassSessionMeetingUrlRequest $request, ClassSession $classSession): RedirectResponse
    {
        $classSession->update(['meeting_url' => $request->validated('meeting_url')]);

        return back()->with('success', $classSession->meeting_url
            ? 'Enlace de la clase publicado. Los estudiantes ya pueden verlo.'
            : 'Enlace de la clase eliminado.');
    }

    public function store(StoreClassSessionRequest $request): RedirectResponse
    {
        ClassSession::create($request->validated());

        return to_route('class-sessions.index')->with('success', 'Clase programada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassSession $classSession): Response
    {
        Gate::authorize('update', $classSession);

        return Inertia::render('ClassSessions/Edit', [
            'classSession' => [
                ...$classSession->toArray(),
                'date' => $classSession->date->toDateString(),
                'start_time' => substr($classSession->start_time, 0, 5),
                'end_time' => substr($classSession->end_time, 0, 5),
            ],
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassSessionRequest $request, ClassSession $classSession, NotifyClassSessionChange $notifyChange): RedirectResponse
    {
        $wasCancelled = $classSession->status !== 'cancelada';
        $previousClassroom = $classSession->classroom;
        $previousSchedule = $classSession->date->format('d/m/Y').' de '.substr($classSession->start_time, 0, 5).' a '.substr($classSession->end_time, 0, 5);

        $classSession->update($request->safe()->except('return_to'));

        if ($wasCancelled && $classSession->status === 'cancelada') {
            $notifyChange->cancelled($classSession);
        } elseif ($classSession->wasChanged(['date', 'start_time', 'end_time'])) {
            $notifyChange->rescheduled($classSession, $previousSchedule);
        } elseif ($classSession->classroom_id !== $previousClassroom->id) {
            $notifyChange->classroomChanged($classSession, $previousClassroom->name);
        }

        return redirect($request->input('return_to') === 'calendar' ? route('calendar.index', ['date' => $classSession->date->toDateString()]) : route('class-sessions.index'))
            ->with('success', 'Clase actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassSession $classSession): RedirectResponse
    {
        Gate::authorize('delete', $classSession);

        if ($blocked = $this->deleteOrBlock($classSession, 'No se puede eliminar la clase: ya tiene asistencias registradas.')) {
            return $blocked;
        }

        return to_route('class-sessions.index')->with('success', 'Clase eliminada correctamente.');
    }
}
