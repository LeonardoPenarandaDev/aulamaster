<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        return Inertia::render('Classrooms/Index', [
            'classrooms' => Classroom::query()
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
        Gate::authorize('create', Classroom::class);

        return Inertia::render('Classrooms/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        Classroom::create($request->validated());

        return to_route('classrooms.index')->with('success', 'Aula creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classroom $classroom): Response
    {
        Gate::authorize('update', $classroom);

        return Inertia::render('Classrooms/Edit', [
            'classroom' => $classroom,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return to_route('classrooms.index')->with('success', 'Aula actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        if ($blocked = $this->deleteOrBlock($classroom, 'No se puede eliminar el aula: tiene clases con asistencias registradas.')) {
            return $blocked;
        }

        return to_route('classrooms.index')->with('success', 'Aula eliminada correctamente.');
    }
}
