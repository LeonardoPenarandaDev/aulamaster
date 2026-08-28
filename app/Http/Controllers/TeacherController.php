<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Teacher::class);

        return Inertia::render('Teachers/Index', [
            'teachers' => Teacher::query()
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
        Gate::authorize('create', Teacher::class);

        return Inertia::render('Teachers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        Teacher::create($request->validated());

        return to_route('teachers.index')->with('success', 'Profesor registrado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher): Response
    {
        Gate::authorize('update', $teacher);

        return Inertia::render('Teachers/Edit', [
            'teacher' => $teacher->load('user:id,email'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update($request->validated());

        return to_route('teachers.index')->with('success', 'Profesor actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        if ($blocked = $this->deleteOrBlock($teacher, 'No se puede eliminar el profesor: tiene clases o asistencias registradas.')) {
            return $blocked;
        }

        return to_route('teachers.index')->with('success', 'Profesor eliminado correctamente.');
    }
}
