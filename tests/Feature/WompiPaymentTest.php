<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Fase 17 del checklist: checkout de pagos en línea con Wompi. Sin
 * credenciales reales no se puede probar el redirect a Wompi ni una
 * transacción real, pero sí las reglas de autorización y el webhook
 * (usando una firma calculada con la misma fórmula que WompiService).
 */
class WompiPaymentTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.public_key' => 'pub_test_123',
            'services.wompi.integrity_secret' => 'integrity-secret',
            'services.wompi.events_secret' => 'events-secret',
        ]);
    }

    public function test_checkout_is_blocked_without_wompi_credentials_configured(): void
    {
        config(['services.wompi.public_key' => null]);

        $student = $this->studentUser();
        $studentRecord = Student::factory()->create(['user_id' => $student->id]);
        $payment = Payment::factory()->create(['student_id' => $studentRecord->id, 'status' => 'pendiente']);

        $this->actingAs($student)->get("/payments/{$payment->id}/pay-online")->assertStatus(503);
    }

    public function test_a_student_cannot_pay_someone_elses_payment(): void
    {
        $student = $this->studentUser();
        Student::factory()->create(['user_id' => $student->id]);
        $otherStudent = Student::factory()->create();
        $payment = Payment::factory()->create(['student_id' => $otherStudent->id, 'status' => 'pendiente']);

        $this->actingAs($student)->get("/payments/{$payment->id}/pay-online")->assertForbidden();
    }

    public function test_a_payment_that_is_not_pending_cannot_be_paid_online(): void
    {
        $student = $this->studentUser();
        $studentRecord = Student::factory()->create(['user_id' => $student->id]);
        $payment = Payment::factory()->create(['student_id' => $studentRecord->id, 'status' => 'pagado']);

        $this->actingAs($student)->get("/payments/{$payment->id}/pay-online")->assertStatus(422);
    }

    public function test_the_owning_student_is_redirected_to_the_wompi_checkout(): void
    {
        $student = $this->studentUser();
        $studentRecord = Student::factory()->create(['user_id' => $student->id]);
        $payment = Payment::factory()->create(['student_id' => $studentRecord->id, 'status' => 'pendiente']);

        $response = $this->actingAs($student)->get("/payments/{$payment->id}/pay-online");

        $response->assertRedirect();
        $this->assertStringStartsWith('https://checkout.wompi.co/p/', $response->headers->get('Location'));
        $this->assertNotNull($payment->fresh()->gateway_reference);
    }

    public function test_webhook_ignores_a_payload_with_an_invalid_signature(): void
    {
        $payment = Payment::factory()->create(['status' => 'pendiente', 'gateway_reference' => 'REF-X']);

        $this->postJson('/webhooks/wompi', [
            'data' => ['transaction' => ['id' => 'wompi-tx-1', 'status' => 'APPROVED', 'reference' => 'REF-X']],
            'signature' => ['properties' => ['data.transaction.status'], 'checksum' => 'not-the-real-checksum'],
            'timestamp' => 1700000000,
        ])->assertOk();

        $this->assertSame('pendiente', $payment->fresh()->status);
    }

    public function test_webhook_marks_the_payment_as_paid_when_the_signature_is_valid_and_approved(): void
    {
        $payment = Payment::factory()->create(['status' => 'pendiente', 'gateway_reference' => 'REF-Y']);

        $timestamp = 1700000000;
        $checksum = hash('sha256', 'APPROVEDREF-Y'.$timestamp.'events-secret');

        $this->postJson('/webhooks/wompi', [
            'data' => ['transaction' => ['id' => 'wompi-tx-2', 'status' => 'APPROVED', 'reference' => 'REF-Y']],
            'signature' => ['properties' => ['data.transaction.status', 'data.transaction.reference'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ])->assertOk();

        $fresh = $payment->fresh();
        $this->assertSame('pagado', $fresh->status);
        $this->assertSame('wompi-tx-2', $fresh->gateway_transaction_id);
        $this->assertSame('APPROVED', $fresh->gateway_status);
        $this->assertNotNull($fresh->paid_at);
    }
}
