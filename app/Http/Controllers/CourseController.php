<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Course::class);

        return Inertia::render('Courses/Index', [
            'courses' => Course::query()
                ->withCount('levels')
                ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
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
        Gate::authorize('create', Course::class);

        return Inertia::render('Courses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::create($request->validated());

        return to_route('courses.index')->with('success', 'Curso creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Courses/Edit', [
            'course' => $course,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return to_route('courses.index')->with('success', 'Curso actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        if ($blocked = $this->deleteOrBlock($course, 'No se puede eliminar el curso: uno de sus niveles tiene matrículas registradas.')) {
            return $blocked;
        }

        return to_route('courses.index')->with('success', 'Curso eliminado correctamente.');
    }
}
