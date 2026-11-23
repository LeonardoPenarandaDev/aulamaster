<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Requisitos del checklist de la gestión académica: el administrador los
 * define (manuales o automáticos) y el personal los marca en cada estudiante.
 */
class ChecklistItemController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ChecklistItems/Index', [
            'items' => ChecklistItem::query()
                ->withCount('marks')
                ->orderBy('position')
                ->orderBy('id')
                ->get(),
            'automaticChecks' => ChecklistItem::AUTOMATIC_CHECKS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChecklistItem::create([
            ...$this->validateItem($request),
            'position' => (int) ChecklistItem::query()->max('position') + 1,
        ]);

        return back()->with('success', 'Requisito agregado.');
    }

    public function update(Request $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->update($this->validateItem($request));

        return back()->with('success', 'Requisito actualizado.');
    }

    /**
     * Sube o baja el requisito una posición.
     */
    public function move(Request $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($checklistItem, $direction) {
            $items = ChecklistItem::query()->orderBy('position')->orderBy('id')->get()->values();
            $index = $items->search(fn (ChecklistItem $item) => $item->id === $checklistItem->id);
            $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $items->has($swapIndex)) {
                return;
            }

            $ordered = $items->all();
            [$ordered[$index], $ordered[$swapIndex]] = [$ordered[$swapIndex], $ordered[$index]];

            foreach (array_values($ordered) as $position => $item) {
                $item->update(['position' => $position + 1]);
            }
        });

        return back();
    }

    /**
     * Elimina el requisito y las marcas que tenía. Para conservar el
     * historial es mejor desactivarlo.
     */
    public function destroy(ChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->delete();

        return back()->with('success', 'Requisito eliminado.');
    }

    /**
     * @return array{name: string, description: string|null, automatic_check: string|null, is_active: bool}
     */
    private function validateItem(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'automatic_check' => ['nullable', Rule::in(array_keys(ChecklistItem::AUTOMATIC_CHECKS))],
            'is_active' => ['boolean'],
        ], [], ['name' => 'requisito', 'automatic_check' => 'revisión automática']);

        return [
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'automatic_check' => $validated['automatic_check'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];
    }
}
