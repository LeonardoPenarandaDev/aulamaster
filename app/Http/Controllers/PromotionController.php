<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\NotifyStudent;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\Course;
use App\Models\Level;
use App\Models\Promotion;
use App\Models\Student;
use App\Notifications\PromotionAnnouncementNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Promotion::class);

        return Inertia::render('Promotions/Index', [
            'promotions' => Promotion::query()
                ->with(['course:id,name', 'level:id,name'])
                ->withCount('enrollments as uses_count')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Promotion::class);

        return Inertia::render('Promotions/Create', [
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name', 'course_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::create($request->validated());

        return to_route('promotions.index')->with('success', 'Promoción creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Promotion $promotion): Response
    {
        Gate::authorize('update', $promotion);

        return Inertia::render('Promotions/Edit', [
            'promotion' => [
                ...$promotion->toArray(),
                'start_date' => $promotion->start_date->toDateString(),
                'end_date' => $promotion->end_date->toDateString(),
            ],
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name', 'course_id']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated());

        return to_route('promotions.index')->with('success', 'Promoción actualizada correctamente.');
    }

    /**
     * Notify active students about the promotion (sección 47 del plan). Se
     * dispara manualmente, no automáticamente al crearla, porque el público
     * (todos los estudiantes activos, o solo los del curso/nivel al que
     * aplica) es una decisión que le corresponde al admin en el momento.
     */
    public function notify(Promotion $promotion, NotifyStudent $notifyStudent): RedirectResponse
    {
        Gate::authorize('update', $promotion);

        $students = Student::query()
            ->where('status', 'activo')
            ->when($promotion->level_id, fn ($query) => $query->whereHas(
                'enrollments',
                fn ($q) => $q->where('level_id', $promotion->level_id)
            ))
            ->when(! $promotion->level_id && $promotion->course_id, fn ($query) => $query->whereHas(
                'enrollments.level',
                fn ($q) => $q->where('course_id', $promotion->course_id)
            ))
            ->get();

        $students->each(fn (Student $student) => $notifyStudent->handle($student, new PromotionAnnouncementNotification($promotion)));

        return back()->with('success', "Promoción notificada a {$students->count()} estudiantes.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        Gate::authorize('delete', $promotion);

        if ($blocked = $this->deleteOrBlock($promotion, 'No se puede eliminar la promoción: ya está aplicada a pagos registrados.')) {
            return $blocked;
        }

        return to_route('promotions.index')->with('success', 'Promoción eliminada correctamente.');
    }
}
