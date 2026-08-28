<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use App\Models\Course;
use App\Models\Level;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Level::class);

        return Inertia::render('Levels/Index', [
            'levels' => Level::query()
                ->with('course:id,name')
                ->when(request('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                ))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLevelRequest $request): RedirectResponse
    {
        Level::create($request->validated());

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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLevelRequest $request, Level $level): RedirectResponse
    {
        $level->update($request->validated());

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

        return to_route('levels.index')->with('success', 'Nivel eliminado correctamente.');
    }
}
