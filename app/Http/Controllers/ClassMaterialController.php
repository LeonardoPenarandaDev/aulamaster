<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassMaterialRequest;
use App\Models\ClassMaterial;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'teacher' => $classSession->teacher?->name ?? 'Sin docente',
                'classroom' => $classSession->classroom->name,
                'present_count' => $classSession->attendances()->get()->where('effective_status', 'presente')->count(),
            ],
            'materials' => $classSession->materials()
                ->latest()
                ->get()
                ->map->toPortalArray(),
            'limits' => [
                'pdf_kb' => config('materials.pdf_max_kb'),
                'image_kb' => config('materials.image_max_kb'),
            ],
        ]);
    }

    public function store(StoreClassMaterialRequest $request, ClassSession $classSession): RedirectResponse
    {
        $data = $request->safe()->except('file');

        if ($file = $request->file('file')) {
            $data = [
                ...$data,
                'url' => null,
                'file_path' => $file->store("class-materials/{$classSession->id}", 'local'),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        }

        $classSession->materials()->create([
            ...$data,
            'created_by_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Material agregado. Lo verán los estudiantes que quedaron presentes en esta clase.');
    }

    /**
     * Archivo privado (PDF o imagen): solo para el docente de la clase, el
     * admin y los estudiantes que lo pueden ver en su portal. Al estudiante
     * en mora lo frena EnsureStudentIsUpToDate.
     */
    public function file(ClassMaterial $classMaterial): StreamedResponse
    {
        Gate::authorize('view', $classMaterial);
        abort_unless($classMaterial->file_path && Storage::disk('local')->exists($classMaterial->file_path), 404);

        return Storage::disk('local')->response($classMaterial->file_path, $classMaterial->file_name, [
            'Content-Type' => $classMaterial->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }

    public function destroy(ClassMaterial $classMaterial): RedirectResponse
    {
        Gate::authorize('delete', $classMaterial);

        $classMaterial->delete();

        return back()->with('success', 'Material eliminado.');
    }
}
