<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtensionRequest;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;

class ExtensionController extends Controller
{
    /**
     * Store a new level extension for an enrollment (sección 27 del plan).
     * The enrollment's estimated_end_date is updated to the new date; the
     * previous date, motivo, usuario y fecha/hora quedan en el historial.
     */
    public function store(StoreExtensionRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $enrollment->extensions()->create([
            'previous_end_date' => $enrollment->estimated_end_date,
            'new_end_date' => $request->validated('new_end_date'),
            'reason' => $request->validated('reason'),
            'notes' => $request->validated('notes'),
            'extended_by_id' => $request->user()->id,
        ]);

        $enrollment->update(['estimated_end_date' => $request->validated('new_end_date')]);

        return back()->with('success', 'Extensión registrada correctamente.');
    }
}
