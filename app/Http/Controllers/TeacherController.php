<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Level;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $globalLevels = Level::globalLevelOptions();
        $levelFilter = $globalLevels->get((string) request('level'));

        return Inertia::render('Teachers/Index', [
            'teachers' => Teacher::query()
                ->with('levels:id,course_id,class_group')
                ->when(request('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                ))
                ->when($levelFilter, fn ($query) => $query->whereHas('levels', fn ($query) => $query->whereIn('levels.id', $levelFilter['level_ids'])))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Teacher $teacher) => [
                    ...$teacher->only(['id', 'code', 'name', 'email', 'status']),
                    'global_levels' => collect($teacher->globalLevelKeys())
                        ->map(fn (string $key) => $globalLevels->get($key)['group'] ?? null)
                        ->filter()
                        ->sort()
                        ->values(),
                ]),
            'globalLevels' => $this->globalLevelChoices($globalLevels),
            'filters' => request()->only('search', 'level'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Teacher::class);

        return Inertia::render('Teachers/Create', [
            'globalLevels' => $this->globalLevelChoices(Level::globalLevelOptions()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $teacher = Teacher::create($request->safe()->except(['portal_access', 'global_levels']));
            $this->syncLevels($teacher, $request->validated('global_levels') ?? []);

            if ($request->boolean('portal_access')) {
                $user = User::create([
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'password' => $teacher->document,
                ]);
                $user->assignRole('profesor');
                $teacher->update(['user_id' => $user->id]);
            }
        });

        return to_route('teachers.index')->with('success', $request->boolean('portal_access')
            ? "Profesor registrado con acceso al portal. Usuario: {$request->validated('email')} · Contraseña: su número de documento."
            : 'Profesor registrado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher): Response
    {
        Gate::authorize('update', $teacher);

        return Inertia::render('Teachers/Edit', [
            'teacher' => [
                ...$teacher->load('user:id,email')->toArray(),
                'global_levels' => $teacher->load('levels:id,course_id,class_group')->globalLevelKeys(),
            ],
            'globalLevels' => $this->globalLevelChoices(Level::globalLevelOptions()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher, RecordAuditEvent $recordAudit): RedirectResponse
    {
        DB::transaction(function () use ($request, $teacher, $recordAudit) {
            $teacher->update($request->safe()->except(['password', 'global_levels']));
            $this->syncLevels($teacher, $request->validated('global_levels') ?? []);

            $user = $teacher->user;

            if (! $user && $request->filled('password')) {
                $user = User::create([
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'password' => $request->validated('password'),
                ]);
                $user->assignRole('profesor');
                $teacher->update(['user_id' => $user->id]);

                return;
            }

            $user?->update([
                'name' => $teacher->name,
                'email' => $teacher->email,
            ]);

            if ($user && $request->filled('password')) {
                $user->forceFill([
                    'password' => $request->validated('password'),
                    'must_change_password' => false,
                ])->save();

                $recordAudit->handle(
                    'contraseña_cambiada',
                    'usuarios',
                    $user,
                    "Contraseña cambiada por el administrador para {$user->name} ({$user->email})",
                );
            }
        });

        return to_route('teachers.index')->with('success', 'Profesor actualizado correctamente.');
    }

    /**
     * Asigna al docente todas las intensidades de los niveles globales
     * marcados.
     *
     * @param  list<string>  $globalLevelKeys
     */
    private function syncLevels(Teacher $teacher, array $globalLevelKeys): void
    {
        $options = Level::globalLevelOptions();

        $teacher->levels()->sync(
            collect($globalLevelKeys)->flatMap(fn (string $key) => $options->get($key)['level_ids'] ?? [])->unique()->values()->all()
        );
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $globalLevels
     * @return Collection<int, array{key: string, label: string, levels: list<string>}>
     */
    private function globalLevelChoices(Collection $globalLevels): Collection
    {
        return $globalLevels->map(fn (array $group) => [
            'key' => $group['key'],
            'label' => $group['group'].($group['course'] ? " · {$group['course']}" : ''),
            'levels' => $group['levels'],
        ])->values();
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
