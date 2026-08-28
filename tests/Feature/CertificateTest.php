<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Fase 16 del checklist: el certificado de nivel aprobado se emite siempre
 * bajo pedido del admin (nunca automático), solo para matrículas ya
 * aprobadas, y cada emisión queda registrada en Auditoría.
 */
class CertificateTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_can_download_the_certificate_of_an_approved_enrollment(): void
    {
        InstitutionSetting::current()->update(['name' => 'Instituto de Prueba']);
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $response = $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_certificate_is_blocked_for_an_enrollment_that_is_not_approved(): void
    {
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'activa']);

        $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate")->assertStatus(422);
    }

    public function test_only_admin_can_issue_certificates_or_edit_institution_settings(): void
    {
        $cajero = $this->cajeroUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $this->actingAs($cajero)->get("/enrollments/{$enrollment->id}/certificate")->assertForbidden();
        $this->actingAs($cajero)->get('/institution-settings')->assertForbidden();
    }

    public function test_issuing_a_certificate_is_recorded_in_the_audit_log(): void
    {
        $admin = $this->adminUser();
        $enrollment = Enrollment::factory()->create(['status' => 'aprobada', 'actual_end_date' => now()]);

        $this->actingAs($admin)->get("/enrollments/{$enrollment->id}/certificate");

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'certificados',
            'action' => 'emitido',
            'auditable_type' => Enrollment::class,
            'auditable_id' => $enrollment->id,
            'user_id' => $admin->id,
        ]);
    }
}
