<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/**
 * Integración con la pasarela de pagos Wompi (Fase 17 del checklist).
 *
 * IMPORTANTE: construida contra la documentación pública de Wompi sin
 * credenciales reales — nadie la ha probado contra el ambiente real de
 * sandbox todavía. Antes de usarla en producción, verificar cada detalle
 * (nombres exactos de campos del webhook, orden de las propiedades del
 * checksum, etc.) contra https://docs.wompi.co una vez se tengan
 * credenciales, y probar el flujo completo con una transacción real de
 * sandbox.
 */
class WompiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.wompi.public_key'))
            && filled(config('services.wompi.integrity_secret'));
    }

    protected function apiBaseUrl(): string
    {
        return config('services.wompi.env') === 'production'
            ? config('services.wompi.production_url')
            : config('services.wompi.sandbox_url');
    }

    /**
     * Firma de integridad requerida por el Web Checkout de Wompi:
     * SHA256(referencia + monto_en_centavos + moneda + secreto_integridad).
     */
    public function integritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference.$amountInCents.$currency.config('services.wompi.integrity_secret'));
    }

    /**
     * URL del Web Checkout de Wompi para pagar un `Payment` pendiente. El
     * estudiante es redirigido ahí; Wompi lo devuelve a `$redirectUrl` al
     * terminar (aprobado, rechazado o pendiente).
     */
    public function checkoutUrl(Payment $payment, string $redirectUrl): string
    {
        $amountInCents = (int) round($payment->final_amount * 100);
        $signature = $this->integritySignature($payment->gateway_reference, $amountInCents);

        $query = http_build_query([
            'public-key' => config('services.wompi.public_key'),
            'currency' => 'COP',
            'amount-in-cents' => $amountInCents,
            'reference' => $payment->gateway_reference,
            'signature:integrity' => $signature,
            'redirect-url' => $redirectUrl,
        ]);

        return config('services.wompi.checkout_url').'?'.$query;
    }

    /**
     * Consulta el estado real de una transacción en la API de Wompi (se usa
     * al volver del checkout, para no depender solo del webhook asíncrono).
     *
     * @return array<string, mixed>|null
     */
    public function fetchTransaction(string $transactionId): ?array
    {
        $response = Http::withToken(config('services.wompi.public_key'))
            ->get("{$this->apiBaseUrl()}/transactions/{$transactionId}");

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * Verifica el checksum que Wompi envía en cada evento de webhook, para
     * confirmar que el payload realmente viene de Wompi y no fue falsificado.
     * Estructura esperada (sección "Eventos" de la documentación de Wompi):
     * concatenar los VALORES de las propiedades listadas en
     * `signature.properties` (leídas de `data` por su dot-path), agregar el
     * `timestamp`, agregar el secreto de eventos, y comparar el SHA256 contra
     * `signature.checksum`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyEventSignature(array $payload): bool
    {
        $properties = data_get($payload, 'signature.properties', []);
        $checksum = data_get($payload, 'signature.checksum');
        $timestamp = data_get($payload, 'timestamp');

        if ($properties === [] || ! $checksum || ! $timestamp) {
            return false;
        }

        $concatenated = collect($properties)
            ->map(fn (string $property) => data_get($payload, $property))
            ->implode('');

        $expected = hash('sha256', $concatenated.$timestamp.config('services.wompi.events_secret'));

        return hash_equals(strtoupper($expected), strtoupper((string) $checksum));
    }
}
