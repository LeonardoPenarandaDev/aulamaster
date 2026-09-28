<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassMaterialRequest;
use App\Models\ClassMaterial;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassMaterialController extends Controller
{
    /**
     * Material de repaso compartido en una clase, gestionado por el profesor
     * que la dicta.
     */
    public function index(ClassSession $classSession): Response
    {
        Gate::authorize('create', [ClassMaterial::class, $classSession]);

        $classSession->load(['level.course:id,name', 'teacher:id,name', 'classroom:id,name']);

        return Inertia::render('ClassMaterials/Index', [
            'classSession' => [
                'id' => $classSession->id,
                'level' => "{$classSession->level->course->name} {$classSession->level->name}",
                'date' => $classSession->date->toDateString(),
                'start_time' => substr($classSession->start_time, 0, 5),
                'end_time' => substr($classSession->end_time, 0, 5),
                'teacher' => $classSession->teacher->name,
                'classroom' => $classSession->classroom->name,
                'present_count' => $classSession->attendances()->get()->where('effective_status', 'presente')->count(),
            ],
            'materials' => $classSession->materials()
                ->latest()
                ->get(['id', 'title', 'url', 'description', 'created_at']),
        ]);
    }

    public function store(StoreClassMaterialRequest $request, ClassSession $classSession): RedirectResponse
    {
        $classSession->materials()->create([
            ...$request->validated(),
            'created_by_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Material agregado. Lo verán los estudiantes que quedaron presentes en esta clase.');
    }

    public function destroy(ClassMaterial $classMaterial): RedirectResponse
    {
        Gate::authorize('delete', $classMaterial);

        $classMaterial->delete();

        return back()->with('success', 'Material eliminado.');
    }
}
