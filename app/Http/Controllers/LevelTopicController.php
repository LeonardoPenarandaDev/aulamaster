<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\LevelTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Temas del nivel global (p. ej. "Elementary 1"), administrados desde la
 * edición de cualquiera de sus intensidades. Se eligen al programar cada
 * clase.
 */
class LevelTopicController extends Controller
{
    public function store(Request $request, Level $level): RedirectResponse
    {
        Gate::authorize('update', $level);

        $validated = $request->validate(['name' => ['required', 'string', 'max:255']], [], ['name' => 'tema']);

        LevelTopic::create([
            'course_id' => $level->course_id,
            'class_group' => $level->class_group,
            'name' => trim($validated['name']),
            'position' => (int) LevelTopic::query()->forLevel($level)->max('position') + 1,
        ]);

        return back()->with('success', 'Tema agregado.');
    }

    public function update(Request $request, Level $level, LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('update', $level);
        abort_unless($levelTopic->belongsToLevelGroup($level), 404);

        $validated = $request->validate(['name' => ['required', 'string', 'max:255']], [], ['name' => 'tema']);
        $levelTopic->update(['name' => trim($validated['name'])]);

        return back()->with('success', 'Tema actualizado.');
    }

    /**
     * Sube o baja el tema una posición.
     */
    public function move(Request $request, Level $level, LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('update', $level);
        abort_unless($levelTopic->belongsToLevelGroup($level), 404);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($level, $levelTopic, $direction) {
            $topics = LevelTopic::query()->forLevel($level)->get()->values();
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
    public function destroy(Level $level, LevelTopic $levelTopic): RedirectResponse
    {
        Gate::authorize('update', $level);
        abort_unless($levelTopic->belongsToLevelGroup($level), 404);

        $levelTopic->delete();

        return back()->with('success', 'Tema eliminado.');
    }
}
