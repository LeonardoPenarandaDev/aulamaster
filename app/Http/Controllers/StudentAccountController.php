<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentAccountController extends Controller
{
    /**
     * "Tu cuenta tiene pagos pendientes": a donde llega el estudiante
     * bloqueado por mora, con sus pagos y el botón de pago en línea (parte 8
     * del plan de mejoras).
     */
    public function blocked(Request $request): Response|RedirectResponse
    {
        $student = $request->user()->student;

        if (! $student?->isBlockedForDebt()) {
            return to_route('dashboard');
        }

        return Inertia::render('StudentAccount/Blocked', [
            'payments' => $student->payments()
                ->whereIn('status', ['vencido', 'pendiente'])
                ->where('type', '!=', 'otro')
                ->orderBy('due_date')
                ->get(['id', 'concept', 'final_amount', 'due_date', 'status'])
                ->map(fn (Payment $payment) => [
                    ...$payment->only(['id', 'concept', 'final_amount', 'status']),
                    'due_date' => $payment->due_date?->toDateString(),
                ]),
        ]);
    }
}
