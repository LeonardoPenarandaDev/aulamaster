<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Level;
use App\Models\LevelTopic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource. Además de la tabla, muestra la
     * ruta de niveles de cada curso (Elementary 1 → Elementary 2 → …).
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Level::class);

        $allLevels = Level::query()->with('course:id,name')->orderBy('position')->get(['id', 'course_id', 'next_level_id', 'name', 'class_group', 'color', 'position']);

        return Inertia::render('Levels/Index', [
            'levels' => Level::query()
                ->with(['course:id,name', 'nextLevel:id,name'])
                ->when(request('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                ))
                ->orderBy('course_id')
                ->orderBy('position')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'routes' => $allLevels
                ->groupBy('course_id')
                ->map(fn ($levels) => [
                    'course' => $levels->first()->course?->name,
                    'paths' => $levels
                        ->reject(fn (Level $level) => $levels->contains('next_level_id', $level->id))
                        ->map(fn (Level $first) => $first->route()->map->only(['id', 'name', 'color'])->values())
                        ->values(),
                ])
                ->sortBy('course')
                ->values(),
            // Niveles que comparten las clases del calendario (mismo curso y grupo).
            'classGroups' => $allLevels
                ->groupBy(fn (Level $level) => $level->course_id.'|'.$level->class_group)
                ->filter(fn ($levels) => $levels->count() > 1)
                ->map(fn ($levels) => [
                    'course' => $levels->first()->course?->name,
                    'group' => $levels->first()->class_group,
                    'levels' => $levels->sortBy('name')->map->only(['id', 'name', 'color'])->values(),
                ])
                ->sortBy(['course', 'group'])
                ->values(),
            'filters' => request()->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Level::class);

        return Inertia::render('Levels/Create', [
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'levelOptions' => $this->levelOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLevelRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $level = Level::create([
                ...$request->validated(),
                'color' => $request->validated('color') ?? Level::DEFAULT_COLOR,
            ]);

            Level::recalculatePositions($level->course_id);
        });

        return to_route('levels.index')->with('success', 'Nivel creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Level $level): Response
    {
        Gate::authorize('update', $level);

        return Inertia::render('Levels/Edit', [
            'level' => [
                ...$level->toArray(),
                'start_date' => $level->start_date?->toDateString(),
                'end_date' => $level->end_date?->toDateString(),
            ],
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'levelOptions' => $this->levelOptions(),
            'topics' => $level->topics()->map->only(['id', 'name', 'position'])->values(),
            'topicSessionCounts' => ClassSession::query()
                ->whereIn('level_topic_id', $level->topics()->pluck('id'))
                ->selectRaw('level_topic_id, count(*) as total')
                ->groupBy('level_topic_id')
                ->pluck('total', 'level_topic_id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLevelRequest $request, Level $level): RedirectResponse
    {
        DB::transaction(function () use ($request, $level) {
            $previousCourseId = $level->course_id;
            $previousClassGroup = $level->class_group;
            $previousMonthlyFee = $level->monthly_fee;

            $level->update([
                ...$request->safe()->except('apply_monthly_fee_to_active'),
                'color' => $request->validated('color') ?? Level::DEFAULT_COLOR,
            ]);

            if ((float) $previousMonthlyFee !== (float) $level->monthly_fee) {
                $this->syncActiveMonthlyFees($level, $previousMonthlyFee, $request->boolean('apply_monthly_fee_to_active'));
            }

            $this->moveTopicsIfGroupWasRenamed($level, $previousCourseId, $previousClassGroup);

            Level::recalculatePositions($level->course_id);
            if ($previousCourseId !== $level->course_id) {
                Level::recalculatePositions($previousCourseId);
            }
        });

        return to_route('levels.index')->with('success', 'Nivel actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $level): RedirectResponse
    {
        Gate::authorize('delete', $level);

        if ($blocked = $this->deleteOrBlock($level, 'No se puede eliminar el nivel: tiene matrículas, clases o evaluaciones registradas.')) {
            return $blocked;
        }

        Level::recalculatePositions($level->course_id);

        return to_route('levels.index')->with('success', 'Nivel eliminado correctamente.');
    }

    /**
     * Cambió la mensualidad del nivel (parte 8 del plan de mejoras). Si se
     * eligió aplicarla, las matrículas vigentes que pagaban el valor del
     * nivel pasan al nuevo desde la próxima mensualidad (la del mes en curso
     * ya está generada). Si no, conservan el valor anterior. Las matrículas
     * con un valor ajustado a mano no cambian en ningún caso.
     */
    private function syncActiveMonthlyFees(Level $level, mixed $previousMonthlyFee, bool $applyToActive): void
    {
        $enrollments = $level->enrollments()
            ->whereIn('status', ['pendiente', 'activa', 'en_recuperacion', 'extendida'])
            ->where(fn ($query) => $query->whereNull('monthly_fee')->when($previousMonthlyFee !== null, fn ($q) => $q->orWhere('monthly_fee', $previousMonthlyFee)));

        $enrollments->update(['monthly_fee' => $applyToActive ? $level->monthly_fee : $previousMonthlyFee]);
    }

    /**
     * Niveles que se pueden elegir como "nivel siguiente" (el formulario
     * filtra por el curso elegido).
     *
     * @return Collection<int, Level>
     */
    /**
     * Si el nivel era el último de su grupo y cambió de grupo (o de curso),
     * sus temas pasan al grupo nuevo para no perderlos.
     */
    private function moveTopicsIfGroupWasRenamed(Level $level, int $previousCourseId, ?string $previousClassGroup): void
    {
        if (! $previousClassGroup || ($previousCourseId === $level->course_id && $previousClassGroup === $level->class_group)) {
            return;
        }

        $groupStillUsed = Level::query()->where('course_id', $previousCourseId)->where('class_group', $previousClassGroup)->exists();
        $newGroupHasTopics = LevelTopic::query()->where('course_id', $level->course_id)->where('class_group', $level->class_group)->exists();

        if (! $groupStillUsed && ! $newGroupHasTopics) {
            LevelTopic::query()
                ->where('course_id', $previousCourseId)
                ->where('class_group', $previousClassGroup)
                ->update(['course_id' => $level->course_id, 'class_group' => $level->class_group]);
        }
    }

    private function levelOptions(): Collection
    {
        return Level::query()->orderBy('name')->get(['id', 'course_id', 'name', 'code', 'class_group']);
    }
}
