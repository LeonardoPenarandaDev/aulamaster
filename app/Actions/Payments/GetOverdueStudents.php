<?php

namespace App\Actions\Payments;

use App\Actions\Contracts\ResolveContractSigner;
use App\Models\InstitutionSetting;
use App\Models\Payment;
use App\Models\PaymentFollowUp;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetOverdueStudents
{
    public function __construct(
        protected ResolveContractSigner $resolveContact,
    ) {}

    /**
     * Estudiantes con pagos vencidos para la cartera en mora (parte 8 del
     * plan de mejoras). Semáforo: amarillo de 1 a 9 días, rojo desde los
     * días de alerta configurados (10). El contacto es el acudiente si el
     * estudiante es menor de edad.
     *
     * @param  array{days?: string|null, level_id?: int|null, uncontacted?: bool}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(array $filters = [], ?CarbonInterface $today = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $redFrom = InstitutionSetting::current()->overdue_alert_days;

        $payments = Payment::query()
            ->overdue()
            ->with(['student.paymentAgreements' => fn ($query) => $query->active(), 'enrollment.level.course:id,name'])
            ->when($filters['level_id'] ?? null, fn ($query, $levelId) => $query->whereHas('enrollment', fn ($q) => $q->where('level_id', $levelId)))
            ->orderBy('due_date')
            ->get();

        $lastFollowUps = PaymentFollowUp::query()
            ->with('contactedBy:id,name')
            ->whereIn('student_id', $payments->pluck('student_id')->unique())
            ->latest('contacted_at')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        return $payments
            ->groupBy('student_id')
            ->map(function (Collection $studentPayments) use ($today, $redFrom, $lastFollowUps) {
                /** @var Student $student */
                $student = $studentPayments->first()->student;
                $oldestDueDate = $studentPayments->min('due_date') ?? $studentPayments->first()->created_at;
                $days = max(1, (int) $oldestDueDate->copy()->startOfDay()->diffInDays($today));
                $contact = $this->resolveContact->handle($student);
                $lastFollowUp = $lastFollowUps->get($student->id);
                $agreement = $student->paymentAgreements->first();

                return [
                    'student' => [
                        'id' => $student->id,
                        'code' => $student->code,
                        'name' => $student->name,
                        'is_minor' => $student->isMinor(),
                    ],
                    'contact' => [
                        'role' => $contact['role'],
                        'name' => $contact['name'],
                        'phone' => $contact['phone'],
                        'email' => $contact['email'],
                    ],
                    'levels' => $studentPayments
                        ->map(fn (Payment $payment) => trim(($payment->enrollment?->level?->course?->name ?? '').' '.($payment->enrollment?->level?->name ?? '')))
                        ->filter()->unique()->values()->all(),
                    'payments' => $studentPayments->map(fn (Payment $payment) => [
                        'id' => $payment->id,
                        'concept' => $payment->concept,
                        'amount' => (float) $payment->final_amount,
                        'due_date' => $payment->due_date?->toDateString(),
                    ])->values()->all(),
                    'total' => (float) $studentPayments->sum('final_amount'),
                    'days_overdue' => $days,
                    'severity' => $days >= $redFrom ? 'rojo' : 'amarillo',
                    'last_follow_up' => $lastFollowUp ? [
                        'contacted_at' => $lastFollowUp->contacted_at->format('Y-m-d H:i'),
                        'channel' => PaymentFollowUp::CHANNELS[$lastFollowUp->channel],
                        'result' => PaymentFollowUp::RESULTS[$lastFollowUp->result],
                        'note' => $lastFollowUp->note,
                        'by' => $lastFollowUp->contactedBy?->name,
                    ] : null,
                    'contacted_since_overdue' => $lastFollowUp !== null && $lastFollowUp->contacted_at->gte($oldestDueDate),
                    'agreement' => $agreement ? [
                        'id' => $agreement->id,
                        'agreed_until' => $agreement->agreed_until->toDateString(),
                        'notes' => $agreement->notes,
                    ] : null,
                ];
            })
            ->when(($filters['days'] ?? null) === 'amarillo', fn (Collection $rows) => $rows->where('severity', 'amarillo'))
            ->when(($filters['days'] ?? null) === 'rojo', fn (Collection $rows) => $rows->where('severity', 'rojo'))
            ->when($filters['uncontacted'] ?? false, fn (Collection $rows) => $rows->where('contacted_since_overdue', false))
            ->sortByDesc('days_overdue')
            ->values();
    }
}
