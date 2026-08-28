<?php

namespace App\Http\Controllers;

use App\Actions\Evaluations\CheckEvaluationEligibility;
use App\Actions\Evaluations\EvaluateLevelCompletion;
use App\Actions\Notifications\NotifyStudent;
use App\Actions\Recovery\DetermineRecoveryTerms;
use App\Http\Requests\StoreEvaluationResultRequest;
use App\Models\Enrollment;
use App\Models\EvaluationResult;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Notifications\EvaluationFailedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationResultController extends Controller
{
    /**
     * Display a listing of the resource (report of all results).
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', EvaluationResult::class);

        return Inertia::render('EvaluationResults/Index', [
            'results' => EvaluationResult::query()
                ->with(['evaluation', 'enrollment.student:id,name,code', 'enrollment.level.course:id,name', 'teacher:id,name'])
                ->when(request('student_id'), fn ($query, $id) => $query->whereHas('enrollment', fn ($q) => $q->where('student_id', $id)))
                ->when(request('level_id'), fn ($query, $id) => $query->whereHas('enrollment', fn ($q) => $q->where('level_id', $id)))
                ->orderByDesc('evaluated_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => request()->only('student_id', 'level_id'),
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form to register a new evaluation result for an enrollment.
     */
    public function create(Enrollment $enrollment, DetermineRecoveryTerms $determineTerms): Response
    {
        Gate::authorize('create', EvaluationResult::class);

        $enrollment->load(['student', 'level.course']);

        $evaluations = $enrollment->level->evaluations()
            ->where('status', 'activo')
            ->with(['results' => fn ($query) => $query->where('enrollment_id', $enrollment->id)->orderBy('attempt_number')])
            ->get()
            ->map(fn ($evaluation) => [
                ...$evaluation->toArray(),
                'next_attempt_terms' => $determineTerms->handle($evaluation, $enrollment),
            ]);

        return Inertia::render('EvaluationResults/Create', [
            'enrollment' => $enrollment,
            'evaluations' => $evaluations,
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'missingRequirements' => app(CheckEvaluationEligibility::class)->handle($enrollment),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreEvaluationResultRequest $request,
        Enrollment $enrollment,
        EvaluateLevelCompletion $evaluateCompletion,
        DetermineRecoveryTerms $determineTerms,
        NotifyStudent $notifyStudent,
    ): RedirectResponse {
        $evaluation = $enrollment->level->evaluations()->findOrFail($request->validated('evaluation_id'));
        $terms = $determineTerms->handle($evaluation, $enrollment);
        $grade = (float) $request->validated('grade');

        $payment = null;
        if ($terms['cost'] > 0) {
            $payment = Payment::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'concept' => "Recuperación - {$evaluation->name}",
                'base_amount' => $terms['cost'],
                'discount_amount' => 0,
                'final_amount' => $terms['cost'],
                'paid_at' => now()->toDateString(),
                'status' => 'pagado',
                'registered_by_id' => $request->user()->id,
            ]);
        }

        $result = EvaluationResult::create([
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'teacher_id' => $request->validated('teacher_id'),
            'attempt_number' => $terms['attempt_number'],
            'is_recovery' => $terms['is_recovery'],
            'cost' => $terms['cost'],
            'payment_status' => $terms['cost'] > 0 ? 'pagado' : 'no_requerido',
            'payment_id' => $payment?->id,
            'evaluated_at' => $request->validated('evaluated_at'),
            'grade' => $grade,
            'result' => $grade >= $evaluation->minimum_grade ? 'aprobado' : 'reprobado',
            'notes' => $request->validated('notes'),
            'registered_by_id' => $request->user()->id,
        ]);

        if ($result->result === 'reprobado') {
            $notifyStudent->handle($enrollment->student, new EvaluationFailedNotification($result));
        }

        $evaluateCompletion->handle($enrollment->fresh());

        return to_route('enrollments.evaluation-results.create', $enrollment)->with('success', 'Resultado registrado correctamente.');
    }
}
