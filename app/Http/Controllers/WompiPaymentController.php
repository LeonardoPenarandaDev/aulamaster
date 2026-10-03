<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Checkout de pagos en línea con Wompi (Fase 17 del checklist). El
 * estudiante paga un `Payment` propio que esté `pendiente`; el registro es
 * el mismo que usa el flujo manual del cajero, así que quien llegue
 * primero (el estudiante en línea, o el cajero registrándolo a mano) no
 * genera un duplicado.
 */
class WompiPaymentController extends Controller
{
    /**
     * Genera (si hace falta) la referencia de la transacción y redirige al
     * Web Checkout de Wompi.
     */
    public function checkout(Request $request, Payment $payment, WompiService $wompi): RedirectResponse
    {
        Gate::authorize('view', $payment);

        // Los vencidos también se pagan en línea: es la forma de desbloquear
        // al estudiante en mora (parte 8 del plan de mejoras).
        abort_unless(in_array($payment->status, ['pendiente', 'vencido'], true), 422, 'Este pago ya no está pendiente.');
        abort_unless($wompi->isConfigured(), 503, 'La pasarela de pagos no está configurada todavía.');

        if (! $payment->gateway_reference) {
            $payment->update([
                'gateway' => 'wompi',
                'gateway_reference' => 'payment-'.$payment->id.'-'.Str::random(8),
                'gateway_status' => 'redirected',
            ]);
        }

        $redirectUrl = $wompi->checkoutUrl($payment, route('payments.online-return'));

        return redirect()->away($redirectUrl);
    }

    /**
     * Wompi devuelve al navegador aquí al terminar el checkout (aprobado,
     * rechazado o pendiente). Se confirma el estado consultando la API en
     * vez de confiar solo en el query string, porque cualquiera podría
     * armar esa URL a mano.
     */
    public function return(Request $request, WompiService $wompi): Response|RedirectResponse
    {
        $transactionId = $request->query('id');

        if (! $transactionId) {
            return to_route('dashboard');
        }

        $transaction = $wompi->fetchTransaction($transactionId);

        if (! $transaction) {
            return Inertia::render('Payments/OnlineReturn', [
                'status' => 'unknown',
                'message' => 'No pudimos confirmar el estado del pago todavía. Si el dinero fue descontado, se reflejará en poco tiempo.',
            ]);
        }

        $payment = Payment::where('gateway_reference', $transaction['reference'] ?? null)->first();

        if ($payment) {
            Gate::authorize('view', $payment);
            $this->applyTransactionStatus($payment, $transaction);
        }

        return Inertia::render('Payments/OnlineReturn', [
            'status' => strtolower($transaction['status'] ?? 'unknown'),
            'message' => match ($transaction['status'] ?? null) {
                'APPROVED' => '¡Pago aprobado! Gracias.',
                'DECLINED' => 'El pago fue rechazado. Podés intentar de nuevo.',
                'PENDING' => 'Tu pago está siendo procesado. Te avisaremos cuando se confirme.',
                default => 'No pudimos determinar el estado final del pago.',
            },
        ]);
    }

    /**
     * Webhook público de Wompi (evento `transaction.updated`). No requiere
     * sesión: la autenticidad se valida con la firma de eventos, no con
     * nuestro CSRF ni con `auth`.
     */
    public function webhook(Request $request, WompiService $wompi): JsonResponse
    {
        $payload = $request->json()->all();

        if (! $wompi->verifyEventSignature($payload)) {
            Log::warning('Webhook de Wompi con firma inválida, ignorado.', ['payload' => $payload]);

            return response()->json(['ignored' => true], 200);
        }

        $transaction = data_get($payload, 'data.transaction');

        if (! $transaction) {
            return response()->json(['ignored' => true], 200);
        }

        $payment = Payment::where('gateway_reference', $transaction['reference'] ?? null)->first();

        if ($payment) {
            $this->applyTransactionStatus($payment, $transaction);
        }

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    protected function applyTransactionStatus(Payment $payment, array $transaction): void
    {
        $update = [
            'gateway_transaction_id' => $transaction['id'] ?? $payment->gateway_transaction_id,
            'gateway_status' => $transaction['status'] ?? $payment->gateway_status,
        ];

        if (($transaction['status'] ?? null) === 'APPROVED' && $payment->status !== 'pagado') {
            $update['status'] = 'pagado';
            $update['paid_at'] = now();
            $update['payment_method'] = 'wompi';
        }

        $payment->update($update);
    }
}
