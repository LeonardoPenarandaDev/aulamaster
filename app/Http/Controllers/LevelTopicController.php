<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Level;
use App\Models\LevelTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Módulo de temas: cada tema se asigna a un nivel global (curso + grupo de
 * clases, p. ej. "Elementary 1") y lo comparten todas sus intensidades
 * (Standard, Semi-Intensivo, Intensivo). Se eligen al programar las clases.
 */
class LevelTopicController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Level::class);

        $groups = $this->globalLevels();
        $sessionCounts = ClassSession::query()
            ->whereNotNull('level_topic_id')
            ->selectRaw('level_topic_id, count(*) as total')
            ->groupBy('level_topic_id')
            ->pluck('total', 'level_topic_id');

        $topics = LevelTopic::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'course_id', 'class_group', 'name', 'position'])
            ->map(fn (LevelTopic $topic) => [
                'id' => $topic->id,
                'name' => $topic->name,
                'group_key' => $this->groupKey($topic->course_id, $topic->class_group),
                'sessions_count' => (int) ($sessionCounts[$topic->id] ?? 0),
            ]);

        return Inertia::render('LevelTopics/Index', [
            'groups' => $groups->values(),
            'topics' => $topics->values(),
            'filters' => ['group' => $request->query('group')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Level::class);

        $validated = $this->validateTopic($request);
        [$courseId, $classGroup] = $this->resolveGroup($validated['group_key']);

        LevelTopic::create([
            'course_id' => $courseId,
            'class_group' => $classGroup,
            'name' => trim($validated['name']),
            'position' => $this->nextPosition($courseId, $classGroup),
        ]);

        return back()->with('success', 'Tema agregado.');
    }

    /**
     * Cambia el nombre o el nivel del tema. No se puede pasar a otro nivel
     * si ya hay clases programadas con él.
     */
    public function update(Request $request, LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('create', Level::class);

        $validated = $this->validateTopic($request);
        [$courseId, $classGroup] = $this->resolveGroup($validated['group_key']);
        $changesGroup = (int) $levelTopic->course_id !== $courseId || $levelTopic->class_group !== $classGroup;

        if ($changesGroup && $levelTopic->classSessions()->exists()) {
            throw ValidationException::withMessages([
                'group_key' => 'Este tema ya tiene clases programadas: no se puede pasar a otro nivel.',
            ]);
        }

        $levelTopic->update([
            'name' => trim($validated['name']),
            ...($changesGroup ? [
                'course_id' => $courseId,
                'class_group' => $classGroup,
                'position' => $this->nextPosition($courseId, $classGroup),
            ] : []),
        ]);

        return back()->with('success', 'Tema actualizado.');
    }

    /**
     * Sube o baja el tema una posición dentro de su nivel.
     */
    public function move(Request $request, LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('create', Level::class);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($levelTopic, $direction) {
            $topics = LevelTopic::query()->inGroup($levelTopic->course_id, $levelTopic->class_group)->orderBy('position')->orderBy('id')->get()->values();
            $index = $topics->search(fn (LevelTopic $topic) => $topic->id === $levelTopic->id);
            $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $topics->has($swapIndex)) {
                return;
            }

            $ordered = $topics->all();
            [$ordered[$index], $ordered[$swapIndex]] = [$ordered[$swapIndex], $ordered[$index]];

            foreach (array_values($ordered) as $position => $topic) {
                $topic->update(['position' => $position + 1]);
            }
        });

        return back();
    }

    /**
     * Las clases que tenían este tema quedan sin tema.
     */
    public function destroy(LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('create', Level::class);

        $levelTopic->delete();

        return back()->with('success', 'Tema eliminado.');
    }

    /**
     * Niveles globales: un grupo de clases por curso, con las intensidades
     * (niveles) que lo comparten.
     *
     * @return Collection<string, array{key: string, course: string|null, group: string, levels: list<string>}>
     */
    private function globalLevels(): Collection
    {
        return Level::query()
            ->with('course:id,name')
            ->whereNotNull('class_group')
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'course_id', 'class_group', 'name', 'position'])
            ->groupBy(fn (Level $level) => $this->groupKey($level->course_id, $level->class_group))
            ->map(fn (Collection $levels, string $key) => [
                'key' => $key,
                'course' => $levels->first()->course?->name,
                'group' => $levels->first()->class_group,
                'levels' => $levels->pluck('name')->sort()->values()->all(),
            ])
            ->sortBy([['course', 'asc'], ['group', 'asc']]);
    }

    /**
     * @return array{name: string, group_key: string}
     */
    private function validateTopic(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_key' => ['required', 'string'],
        ], [], ['name' => 'tema', 'group_key' => 'nivel']);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolveGroup(string $key): array
    {
        $group = $this->globalLevels()->get($key);

        if (! $group) {
            throw ValidationException::withMessages(['group_key' => 'Elige un nivel válido.']);
        }

        return [(int) strtok($key, '|'), $group['group']];
    }

    private function groupKey(int|string $courseId, ?string $classGroup): string
    {
        return $courseId.'|'.$classGroup;
    }

    private function nextPosition(int $courseId, string $classGroup): int
    {
        return (int) LevelTopic::query()->inGroup($courseId, $classGroup)->max('position') + 1;
    }
}
