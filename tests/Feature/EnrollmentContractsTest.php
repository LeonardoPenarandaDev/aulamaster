<?php

namespace Tests\Feature;

use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Notifications\EnrollmentActivatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithContracts;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Partes 6.3, 6.5, 6.6 y 7 del plan de mejoras: qué contratos se generan
 * para una matrícula, su gestión desde la oficina y la activación
 * automática de la matrícula.
 */
class EnrollmentContractsTest extends TestCase
{
    use InteractsWithContracts, InteractsWithRoles, RefreshDatabase;

    public function test_generates_the_published_templates_that_apply_to_the_student(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $matricula = $this->publishedTemplate(['name' => 'Contrato de matrícula']);
        $this->publishedTemplate(['name' => 'Autorización de menores', 'requires_guardian' => true]);
        ContractTemplate::factory()->create(['name' => 'Borrador']);
        ContractTemplate::factory()->create(['name' => 'Archivada', 'status' => 'archivado']);

        $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.contracts.store', $enrollment), [
                'special_clauses' => [$matricula->id => 'Pago en dos cuotas.'],
            ])
            ->assertSessionHasNoErrors();

        $contracts = $enrollment->contractSignatures()->with('template')->get();
        $this->assertSame(['Contrato de matrícula'], $contracts->pluck('template.name')->all());

        $contract = $contracts->first();
        $this->assertSame('pendiente', $contract->status);
        $this->assertSame(1, $contract->template_version);
        $this->assertStringContainsString($enrollment->student->name, $contract->rendered_body);
        $this->assertStringEndsWith('Pago en dos cuotas.', $contract->rendered_body);
        $this->assertSame(hash('sha256', $contract->rendered_body), $contract->content_hash);
    }

    public function test_minor_only_templates_are_generated_for_minors(): void
    {
        $enrollment = $this->enrollmentReadyForContracts($this->minorStudentAttributes());
        $this->publishedTemplate(['name' => 'Autorización de menores', 'requires_guardian' => true]);

        $this->actingAs($this->adminUser())->post(route('enrollments.contracts.store', $enrollment))->assertSessionHasNoErrors();

        $this->assertSame(1, $enrollment->contractSignatures()->count());
    }

    public function test_per_student_contracts_already_signed_are_skipped_and_nothing_is_duplicated(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $data = $this->publishedTemplate(['name' => 'Tratamiento de datos', 'scope' => 'alumno']);
        $this->publishedTemplate(['name' => 'Contrato de matrícula']);
        $previous = Enrollment::factory()->create(['student_id' => $enrollment->student_id, 'status' => 'aprobada']);
        ContractSignature::factory()->signed()->create(['enrollment_id' => $previous->id, 'contract_template_id' => $data->id]);

        $admin = $this->adminUser();
        $this->actingAs($admin)->post(route('enrollments.contracts.store', $enrollment))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('enrollments.contracts.store', $enrollment))->assertSessionHasErrors('contracts');

        $this->assertSame(['Contrato de matrícula'], $enrollment->contractSignatures()->with('template')->get()->pluck('template.name')->all());
    }

    public function test_contracts_cannot_be_generated_with_incomplete_student_data(): void
    {
        $enrollment = $this->enrollmentReadyForContracts(['birth_date' => now()->subYears(12)]);
        $this->publishedTemplate();

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.contracts.store', $enrollment))
            ->assertSessionHasErrors('contracts');

        $this->assertSame(0, ContractSignature::query()->count());
    }

    public function test_a_voided_contract_can_be_generated_again(): void
    {
        $admin = $this->adminUser();
        $enrollment = $this->enrollmentReadyForContracts();
        $this->publishedTemplate();

        $this->actingAs($admin)->post(route('enrollments.contracts.store', $enrollment));
        $contract = $enrollment->contractSignatures()->firstOrFail();

        $this->actingAs($admin)->post(route('contract-signatures.void', $contract), ['reason' => 'Precio equivocado'])->assertSessionHasNoErrors();
        $this->assertSame('anulado', $contract->fresh()->status);
        $this->assertSame('Precio equivocado', $contract->fresh()->void_reason);

        $this->actingAs($admin)->post(route('enrollments.contracts.store', $enrollment))->assertSessionHasNoErrors();
        $this->assertSame(1, $enrollment->contractSignatures()->open()->count());
    }

    public function test_cashier_and_teacher_cannot_manage_contracts(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $this->publishedTemplate();

        foreach ([$this->cajeroUser(), $this->teacherUser()] as $user) {
            $this->actingAs($user)->post(route('enrollments.contracts.store', $enrollment))->assertForbidden();
        }

        $this->actingAs($this->cajeroUser())
            ->get(route('enrollments.edit', $enrollment))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('contracts', null));
    }

    public function test_enrollment_page_lists_contracts_and_index_filters_pending_ones(): void
    {
        $withPending = $this->enrollmentReadyForContracts();
        $this->enrollmentReadyForContracts();
        ContractSignature::factory()->create(['enrollment_id' => $withPending->id]);
        $secretaria = $this->secretariaUser();

        $this->actingAs($secretaria)
            ->get(route('enrollments.edit', $withPending))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('contracts.items', 1)
                ->where('contracts.items.0.status', 'pendiente')
                ->where('contracts.signer.role', 'alumno')
            );

        $this->actingAs($secretaria)
            ->get(route('enrollments.index', ['contracts' => 'pendientes']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('enrollments.data', 1)
                ->where('enrollments.data.0.id', $withPending->id)
                ->where('enrollments.data.0.open_contracts_count', 1)
            );
    }

    public function test_enrollment_activates_when_mandatory_contracts_are_signed_and_a_payment_is_paid(): void
    {
        Notification::fake();
        $enrollment = $this->enrollmentReadyForContracts();
        $mandatory = $this->publishedTemplate();
        $this->publishedTemplate(['acceptance_mode' => 'opcional']);

        ContractSignature::factory()->signed()->create(['enrollment_id' => $enrollment->id, 'contract_template_id' => $mandatory->id]);
        $this->assertSame('pendiente', $enrollment->fresh()->status);

        $payment = Payment::factory()->create(['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'status' => 'pendiente']);
        $this->assertSame('pendiente', $enrollment->fresh()->status);

        $payment->update(['status' => 'pagado']);

        $this->assertSame('activa', $enrollment->fresh()->status);
        Notification::assertSentOnDemand(EnrollmentActivatedNotification::class);
    }

    public function test_enrollment_stays_pending_while_a_mandatory_contract_is_unsigned(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $this->publishedTemplate();

        Payment::factory()->create(['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'status' => 'pagado']);

        $this->assertSame('pendiente', $enrollment->fresh()->status);
    }
}
