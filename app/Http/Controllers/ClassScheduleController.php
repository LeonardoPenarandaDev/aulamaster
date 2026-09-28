<?php

namespace App\Http\Controllers;

use App\Actions\Scheduling\GenerateClassSessionsFromSchedule;
use App\Http\Requests\StoreClassScheduleRequest;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', ClassSchedule::class);

        return Inertia::render('ClassSchedules/Index', [
            'classSchedules' => ClassSchedule::query()
                ->with(['level.course:id,name', 'teacher:id,name', 'classroom:id,name'])
                ->withCount('classSessions')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', ClassSchedule::class);

        return Inertia::render('ClassSchedules/Create', [
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage and generate its class sessions.
     */
    public function store(StoreClassScheduleRequest $request, GenerateClassSessionsFromSchedule $generate): RedirectResponse
    {
        $schedule = ClassSchedule::create($request->validated());

        $result = $generate->handle($schedule);

        $message = "Horario creado. Se generaron {$result['created']} clases.";
        if ($result['skipped'] > 0) {
            $message .= " Se omitieron {$result['skipped']} por conflictos de aula/profesor.";
        }

        return to_route('class-schedules.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage. The class sessions already
     * generated are kept as independent records (per the plan's requirement
     * that every class stays a standalone historical record).
     */
    public function destroy(ClassSchedule $classSchedule): RedirectResponse
    {
        Gate::authorize('delete', $classSchedule);

        $deletedSessions = DB::transaction(function () use ($classSchedule) {
            $deleted = 0;

            $classSchedule->classSessions()
                ->whereDate('date', '>=', today())
                ->whereDoesntHave('attendances')
                ->get()
                ->each(function ($session) use (&$deleted) {
                    $session->delete();
                    $deleted++;
                });

            $classSchedule->delete();

            return $deleted;
        });

        return to_route('class-schedules.index')->with(
            'success',
            "Horario eliminado junto con {$deletedSessions} clases futuras. Las clases pasadas o con asistencia se conservan en el historial."
        );
    }
}
