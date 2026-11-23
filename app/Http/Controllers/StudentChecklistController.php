<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Student;
use App\Models\StudentChecklistMark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * El personal marca (o desmarca) en el estudiante un requisito manual del
 * checklist, como "Kit de inicio entregado". Los automáticos no se marcan:
 * la plataforma los revisa sola.
 */
class StudentChecklistController extends Controller
{
    public function store(Request $request, Student $student, ChecklistItem $checklistItem): RedirectResponse
    {
        Gate::authorize('update', $student);

        if (! $checklistItem->isManual() || ! $checklistItem->is_active) {
            return back()->with('error', 'Este requisito lo revisa la plataforma automáticamente.');
        }

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:255']]);

        StudentChecklistMark::query()->updateOrCreate(
            ['student_id' => $student->id, 'checklist_item_id' => $checklistItem->id],
            ['checked_by_id' => $request->user()->id, 'notes' => $validated['notes'] ?? null],
        );

        return back()->with('success', "«{$checklistItem->name}» marcado como cumplido.");
    }

    public function destroy(Student $student, ChecklistItem $checklistItem): RedirectResponse
    {
        Gate::authorize('update', $student);

        StudentChecklistMark::query()
            ->where('student_id', $student->id)
            ->where('checklist_item_id', $checklistItem->id)
            ->delete();

        return back()->with('success', "«{$checklistItem->name}» desmarcado.");
    }
}
