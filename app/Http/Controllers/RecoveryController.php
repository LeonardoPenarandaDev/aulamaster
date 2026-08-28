<?php

namespace App\Http\Controllers;

use App\Actions\Recovery\GetPendingRecoveries;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecoveryController extends Controller
{
    /**
     * Display the dashboard of pending recoveries (sección 25 del plan).
     */
    public function index(GetPendingRecoveries $getPendingRecoveries): Response
    {
        Gate::authorize('viewAny', Enrollment::class);

        $pending = $getPendingRecoveries->handle()->map(fn ($item) => [
            'enrollment_id' => $item['enrollment']->id,
            'student' => $item['enrollment']->student->only(['id', 'name', 'code']),
            'level' => [
                'id' => $item['enrollment']->level->id,
                'name' => $item['enrollment']->level->name,
                'course' => $item['enrollment']->level->course->name,
            ],
            'evaluation' => $item['evaluation']->only(['id', 'name']),
            'last_attempt' => [
                'attempt_number' => $item['last_attempt']->attempt_number,
                'grade' => $item['last_attempt']->grade,
                'evaluated_at' => $item['last_attempt']->evaluated_at->toDateString(),
            ],
            'deadline' => $item['deadline']->toDateString(),
            'overdue' => $item['overdue'],
            'terms' => $item['terms'],
        ]);

        return Inertia::render('Recovery/Index', [
            'pending' => $pending,
        ]);
    }
}
