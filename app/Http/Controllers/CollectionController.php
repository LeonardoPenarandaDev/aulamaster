<?php

namespace App\Http\Controllers;

use App\Actions\Payments\GetOverdueStudents;
use App\Models\Level;
use App\Models\PaymentAgreement;
use App\Models\PaymentFollowUp;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Cartera en mora" (parte 8 del plan de mejoras): admin, cajero y
 * secretaria contactan a los estudiantes en mora y registran cada contacto.
 * Los acuerdos de pago los firman el admin y el cajero.
 */
class CollectionController extends Controller
{
    /**
     * Display the overdue students.
     */
    public function index(Request $request, GetOverdueStudents $getOverdueStudents): Response
    {
        $filters = $request->validate([
            'days' => ['nullable', Rule::in(['todos', 'amarillo', 'rojo'])],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'uncontacted' => ['nullable', 'boolean'],
        ]);

        $filters = [
            'days' => $filters['days'] ?? 'todos',
            'level_id' => isset($filters['level_id']) ? (int) $filters['level_id'] : null,
            'uncontacted' => (bool) ($filters['uncontacted'] ?? false),
        ];

        $students = $getOverdueStudents->handle($filters);

        return Inertia::render('Collections/Index', [
            'students' => $students,
            'totals' => [
                'students' => $students->count(),
                'amount' => $students->sum('total'),
            ],
            'filters' => $filters,
            'levels' => Level::query()->with('course:id,name')->orderBy('name')->get(['id', 'name', 'course_id']),
            'channels' => PaymentFollowUp::CHANNELS,
            'results' => PaymentFollowUp::RESULTS,
            'canManageAgreements' => $request->user()->hasRole(['admin', 'cajero']),
        ]);
    }

    /**
     * Register a contact with the student or their guardian.
     */
    public function storeFollowUp(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(array_keys(PaymentFollowUp::CHANNELS))],
            'result' => ['required', Rule::in(array_keys(PaymentFollowUp::RESULTS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $student->paymentFollowUps()->create([
            ...$validated,
            'contacted_by_id' => $request->user()->id,
            'contacted_at' => now(),
        ]);

        return back()->with('success', "Contacto con {$student->name} registrado.");
    }

    /**
     * Create a payment agreement: el estudiante se desbloquea hasta la fecha
     * acordada aunque siga con pagos vencidos.
     */
    public function storeAgreement(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'cajero']), 403);

        $validated = $request->validate([
            'agreed_until' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addMonths(3)->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'agreed_until.before_or_equal' => 'El acuerdo no puede ser de más de 3 meses.',
        ]);

        $student->paymentAgreements()->active()->update(['cancelled_at' => now()]);
        $student->paymentAgreements()->create([...$validated, 'created_by_id' => $request->user()->id]);

        $student->paymentFollowUps()->create([
            'channel' => 'presencial',
            'result' => 'promesa_pago',
            'note' => 'Acuerdo de pago hasta el '.$validated['agreed_until'].($validated['notes'] ?? null ? ': '.$validated['notes'] : '.'),
            'contacted_by_id' => $request->user()->id,
            'contacted_at' => now(),
        ]);

        return back()->with('success', "Acuerdo de pago registrado: {$student->name} queda desbloqueado hasta el {$validated['agreed_until']}.");
    }

    /**
     * Cancel an agreement (el estudiante vuelve a quedar bloqueado si sigue
     * en mora).
     */
    public function cancelAgreement(Request $request, PaymentAgreement $paymentAgreement): RedirectResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'cajero']), 403);

        $paymentAgreement->update(['cancelled_at' => now()]);

        return back()->with('success', 'Acuerdo de pago cancelado.');
    }
}
