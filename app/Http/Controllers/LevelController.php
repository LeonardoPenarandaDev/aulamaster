<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use App\Models\Course;
use App\Models\Level;
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

        $allLevels = Level::query()->with('course:id,name')->orderBy('position')->get(['id', 'course_id', 'next_level_id', 'name', 'color', 'position']);

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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLevelRequest $request, Level $level): RedirectResponse
    {
        DB::transaction(function () use ($request, $level) {
            $previousCourseId = $level->course_id;
            $previousMonthlyFee = $level->monthly_fee;

            $level->update([
                ...$request->safe()->except('apply_monthly_fee_to_active'),
                'color' => $request->validated('color') ?? Level::DEFAULT_COLOR,
            ]);

            if ((float) $previousMonthlyFee !== (float) $level->monthly_fee) {
                $this->syncActiveMonthlyFees($level, $previousMonthlyFee, $request->boolean('apply_monthly_fee_to_active'));
            }

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
    private function levelOptions(): Collection
    {
        return Level::query()->orderBy('name')->get(['id', 'course_id', 'name', 'code']);
    }
}
