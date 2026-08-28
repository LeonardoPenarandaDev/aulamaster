<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\WompiService;
use Tests\TestCase;

/**
 * Fase 17 del checklist: la integración con Wompi está construida sin
 * credenciales reales, así que lo único verificable de forma automatizada
 * es la matemática de las firmas (determinista, no depende de la red).
 */
class WompiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.public_key' => 'pub_test_123',
            'services.wompi.integrity_secret' => 'integrity-secret',
            'services.wompi.events_secret' => 'events-secret',
        ]);
    }

    public function test_integrity_signature_matches_the_documented_formula(): void
    {
        $service = new WompiService;

        $expected = hash('sha256', 'REF-1'.(100000).'COP'.'integrity-secret');

        $this->assertSame($expected, $service->integritySignature('REF-1', 100000));
    }

    public function test_checkout_url_contains_the_signed_reference_and_amount(): void
    {
        $service = new WompiService;
        $payment = Payment::factory()->make(['final_amount' => 1000, 'gateway_reference' => 'REF-2']);

        $url = $service->checkoutUrl($payment, 'https://app.test/return');

        $this->assertStringContainsString('reference=REF-2', $url);
        $this->assertStringContainsString('amount-in-cents=100000', $url);
        $this->assertStringContainsString('public-key=pub_test_123', $url);
        $this->assertStringContainsString(urlencode('https://app.test/return'), $url);
    }

    public function test_a_correctly_signed_event_payload_is_accepted(): void
    {
        $service = new WompiService;

        $timestamp = 1700000000;
        $checksum = hash('sha256', 'APPROVEDREF-3'.$timestamp.'events-secret');

        $payload = [
            'data' => ['transaction' => ['status' => 'APPROVED', 'reference' => 'REF-3']],
            'signature' => ['properties' => ['data.transaction.status', 'data.transaction.reference'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->assertTrue($service->verifyEventSignature($payload));
    }

    public function test_a_tampered_event_payload_is_rejected(): void
    {
        $service = new WompiService;

        $timestamp = 1700000000;
        $checksum = hash('sha256', 'APPROVEDREF-3'.$timestamp.'events-secret');

        $payload = [
            // El monto fue alterado después de calcular el checksum original.
            'data' => ['transaction' => ['status' => 'APPROVED', 'reference' => 'REF-3-TAMPERED']],
            'signature' => ['properties' => ['data.transaction.status', 'data.transaction.reference'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->assertFalse($service->verifyEventSignature($payload));
    }

    public function test_is_configured_reflects_whether_the_keys_are_set(): void
    {
        $service = new WompiService;
        $this->assertTrue($service->isConfigured());

        config(['services.wompi.public_key' => null]);
        $this->assertFalse($service->isConfigured());
    }
}
