<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Models\Evaluation;
use App\Models\Level;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Evaluation::class);

        return Inertia::render('Evaluations/Index', [
            'evaluations' => Evaluation::query()
                ->with('level.course:id,name')
                ->when(request('level_id'), fn ($query, $id) => $query->where('level_id', $id))
                ->orderBy('level_id')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => request()->only('level_id'),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Evaluation::class);

        return Inertia::render('Evaluations/Create', [
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvaluationRequest $request): RedirectResponse
    {
        Evaluation::create($request->validated());

        return to_route('evaluations.index')->with('success', 'Evaluación creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Evaluation $evaluation): Response
    {
        Gate::authorize('update', $evaluation);

        return Inertia::render('Evaluations/Edit', [
            'evaluation' => $evaluation,
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        $evaluation->update($request->validated());

        return to_route('evaluations.index')->with('success', 'Evaluación actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Evaluation $evaluation): RedirectResponse
    {
        Gate::authorize('delete', $evaluation);

        if ($blocked = $this->deleteOrBlock($evaluation, 'No se puede eliminar la evaluación: ya tiene resultados registrados.')) {
            return $blocked;
        }

        return to_route('evaluations.index')->with('success', 'Evaluación eliminada correctamente.');
    }
}
