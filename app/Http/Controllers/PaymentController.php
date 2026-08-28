<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\NotifyStudent;
use App\Actions\Payments\GetAccountStatement;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Student;
use App\Notifications\PaymentOverdueNotification;
use App\Notifications\PaymentPendingNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Payment::class);

        return Inertia::render('Payments/Index', [
            'payments' => Payment::query()
                ->with(['student:id,name,code', 'enrollment.level:id,name'])
                ->when(request('student_id'), fn ($query, $id) => $query->where('student_id', $id))
                ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => request()->only('student_id', 'status'),
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Payment::class);

        return Inertia::render('Payments/Create', [
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'enrollments' => Enrollment::query()->with('level:id,name')->get(['id', 'student_id', 'level_id']),
            'promotions' => Promotion::query()->where('status', 'activo')->orderBy('name')->get(['id', 'name', 'discount_type', 'value']),
            'referrals' => Referral::query()->with(['referrer:id,name', 'referred:id,name'])->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request, NotifyStudent $notifyStudent): RedirectResponse
    {
        $payment = Payment::create([
            ...$request->validated(),
            'registered_by_id' => $request->user()->id,
        ]);

        if ($payment->status === 'pendiente') {
            $notifyStudent->handle($payment->student, new PaymentPendingNotification($payment));
        }

        return to_route('payments.index')->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment): Response
    {
        Gate::authorize('update', $payment);

        return Inertia::render('Payments/Edit', [
            'payment' => [
                ...$payment->toArray(),
                'paid_at' => $payment->paid_at?->toDateString(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment, NotifyStudent $notifyStudent): RedirectResponse
    {
        $wasOverdue = $payment->status === 'vencido';

        $payment->update($request->validated());

        if (! $wasOverdue && $payment->status === 'vencido') {
            $notifyStudent->handle($payment->student, new PaymentOverdueNotification($payment));
        }

        return to_route('payments.index')->with('success', 'Pago actualizado correctamente.');
    }

    /**
     * Display the account statement for a student (sección 34 del plan).
     */
    public function accountStatement(Student $student, GetAccountStatement $getAccountStatement): Response
    {
        Gate::authorize('viewAny', Payment::class);

        return Inertia::render('Payments/AccountStatement', [
            'student' => $student,
            'statement' => $getAccountStatement->handle($student),
            'payments' => $student->payments()->orderByDesc('id')->get(),
        ]);
    }
}
