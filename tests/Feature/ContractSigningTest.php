<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ContractSignature;
use App\Models\Payment;
use App\Models\Student;
use App\Notifications\ContractSignedNotification;
use App\Notifications\ContractSigningLinkNotification;
use App\Notifications\ContractVerificationCodeNotification;
use App\Notifications\SignedContractCopyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithContracts;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Partes 6.4, 6.7 y 6.8 del plan de mejoras: firma en la oficina y a
 * distancia, con su evidencia, el PDF y la foto del documento cifrada.
 */
class ContractSigningTest extends TestCase
{
    use InteractsWithContracts, InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function officeData(array $overrides = []): array
    {
        return array_merge([
            'signature_png' => $this->signaturePng(),
            'signer_name' => 'Ana Gómez',
            'signer_document' => 'CC 1020304050',
            'signer_role' => 'alumno',
            'identity_verified' => true,
            'timezone' => 'America/Bogota',
            'id_front' => UploadedFile::fake()->image('anverso.jpg'),
        ], $overrides);
    }

    public function test_office_signature_stores_evidence_pdf_and_encrypted_id_photo(): void
    {
        $secretaria = $this->secretariaUser();
        $contract = ContractSignature::factory()->create(['enrollment_id' => $this->enrollmentReadyForContracts()->id]);

        $this->actingAs($secretaria)->get(route('enrollments.contracts.sign', $contract->enrollment_id))->assertOk();

        $this->actingAs($secretaria)
            ->post(route('contract-signatures.sign', $contract), $this->officeData())
            ->assertRedirect(route('enrollments.contracts.sign', $contract->enrollment_id))
            ->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame('firmado', $contract->status);
        $this->assertSame('oficina', $contract->signing_method);
        $this->assertSame('acepta', $contract->decision);
        $this->assertSame($secretaria->id, $contract->identity_verified_by_id);
        $this->assertSame('America/Bogota', $contract->signer_timezone);
        $this->assertNotNull($contract->ip);
        Storage::disk('local')->assertExists([$contract->signature_path, $contract->pdf_path, $contract->id_front_path]);

        $storedPhoto = Storage::disk('local')->get($contract->id_front_path);
        $this->assertStringNotContainsString('JFIF', $storedPhoto);
        $this->assertNotEmpty(Crypt::decryptString($storedPhoto));

        $this->assertDatabaseHas(AuditLog::class, ['auditable_type' => ContractSignature::class, 'auditable_id' => $contract->id, 'action' => 'firmado']);
    }

    public function test_office_signature_requires_identity_check_photo_and_signature(): void
    {
        $contract = ContractSignature::factory()->create(['enrollment_id' => $this->enrollmentReadyForContracts()->id]);

        $this->actingAs($this->secretariaUser())
            ->post(route('contract-signatures.sign', $contract), $this->officeData(['identity_verified' => false, 'signature_png' => 'data:image/png;base64,AAAA']))
            ->assertSessionHasErrors(['identity_verified']);

        $this->actingAs($this->secretariaUser())
            ->post(route('contract-signatures.sign', $contract), $this->officeData(['id_front' => null]))
            ->assertSessionHasErrors('id_front');

        $this->actingAs($this->secretariaUser())
            ->post(route('contract-signatures.sign', $contract), $this->officeData(['signature_png' => 'data:image/png;base64,AAAA']))
            ->assertSessionHasErrors('signature_png');

        $this->assertSame('pendiente', $contract->fresh()->status);
    }

    public function test_next_contract_reuses_the_id_photo_taken_moments_before(): void
    {
        $secretaria = $this->secretariaUser();
        $enrollment = $this->enrollmentReadyForContracts();
        [$first, $second] = ContractSignature::factory()->count(2)->create(['enrollment_id' => $enrollment->id]);

        $this->actingAs($secretaria)->post(route('contract-signatures.sign', $first), $this->officeData())->assertSessionHasNoErrors();
        $this->actingAs($secretaria)->post(route('contract-signatures.sign', $second), $this->officeData(['id_front' => null]))->assertSessionHasNoErrors();

        $this->assertSame($first->fresh()->id_front_path, $second->fresh()->id_front_path);
    }

    public function test_optional_image_authorization_updates_the_student_and_can_be_revoked(): void
    {
        $studentUser = $this->studentUser();
        $enrollment = $this->enrollmentReadyForContracts(['user_id' => $studentUser->id]);
        $template = $this->publishedTemplate(['type' => 'imagenes', 'acceptance_mode' => 'opcional']);
        $contract = ContractSignature::factory()->create(['enrollment_id' => $enrollment->id, 'contract_template_id' => $template->id]);

        $this->actingAs($this->secretariaUser())
            ->post(route('contract-signatures.sign', $contract), $this->officeData())
            ->assertSessionHasErrors('decision');

        $this->actingAs($this->secretariaUser())
            ->post(route('contract-signatures.sign', $contract), $this->officeData(['decision' => 'acepta']))
            ->assertSessionHasNoErrors();

        $student = $enrollment->student->fresh();
        $this->assertTrue($student->image_consent);

        $this->actingAs($studentUser)->get(route('student-contracts.index'))->assertOk();
        $this->actingAs($studentUser)->get(route('contract-signatures.pdf', $contract))->assertOk();
        $this->actingAs($studentUser)->post(route('student-contracts.revoke-image-consent'))->assertSessionHasNoErrors();

        $this->assertFalse($student->fresh()->image_consent);
    }

    public function test_students_cannot_download_other_students_contracts(): void
    {
        $studentUser = $this->studentUser();
        Student::factory()->create(['user_id' => $studentUser->id]);
        $other = ContractSignature::factory()->signed()->create();

        $this->actingAs($studentUser)->get(route('contract-signatures.pdf', $other))->assertForbidden();
    }

    public function test_viewing_the_id_photo_is_restricted_and_audited(): void
    {
        $contract = ContractSignature::factory()->create(['enrollment_id' => $this->enrollmentReadyForContracts()->id]);
        $this->actingAs($this->secretariaUser())->post(route('contract-signatures.sign', $contract), $this->officeData());

        $this->actingAs($this->cajeroUser())->get(route('contract-signatures.id-photo', [$contract, 'anverso']))->assertForbidden();

        $admin = $this->adminUser();
        $this->actingAs($admin)
            ->get(route('contract-signatures.id-photo', [$contract, 'anverso']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->assertDatabaseHas(AuditLog::class, ['user_id' => $admin->id, 'action' => 'foto_documento_vista', 'auditable_id' => $contract->id]);
    }

    public function test_signing_the_last_mandatory_contract_activates_a_paid_enrollment(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $contract = ContractSignature::factory()->create(['enrollment_id' => $enrollment->id]);
        Payment::factory()->create(['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'status' => 'pagado']);
        $this->assertSame('pendiente', $enrollment->fresh()->status);

        $this->actingAs($this->secretariaUser())->post(route('contract-signatures.sign', $contract), $this->officeData());

        $this->assertSame('activa', $enrollment->fresh()->status);
    }

    public function test_remote_signing_with_email_code_for_a_minor_signed_by_the_guardian(): void
    {
        $secretaria = $this->secretariaUser();
        $enrollment = $this->enrollmentReadyForContracts($this->minorStudentAttributes());
        $contract = ContractSignature::factory()->create(['enrollment_id' => $enrollment->id]);

        $this->actingAs($secretaria)
            ->post(route('enrollments.contracts.send', $enrollment), ['via' => 'correo'])
            ->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame('enviado', $contract->status);
        $url = null;
        Notification::assertSentOnDemand(ContractSigningLinkNotification::class, function ($notification, $channels, AnonymousNotifiable $notifiable) use (&$url) {
            $url = $notification->actionUrl();

            return $notifiable->routes['mail'] === 'marta@correo.test';
        });

        auth()->logout();

        $this->get($url)->assertOk();
        $this->assertSame('abierto', $contract->fresh()->status);

        $signUrl = route('contracts.remote.sign', [$enrollment, $contract]);
        $this->post($signUrl, ['signature_png' => $this->signaturePng(), 'accepted_terms' => true])->assertSessionHasErrors('code');

        $code = null;
        $this->post(route('contracts.remote.code', $enrollment))->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ContractVerificationCodeNotification::class, function ($notification) use (&$code) {
            preg_match('/(\d{6})/', $notification->title(), $matches);
            $code = $matches[1];

            return true;
        });

        $this->post(route('contracts.remote.code', $enrollment), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('contracts.remote.code', $enrollment), ['code' => $code])->assertSessionHasNoErrors();

        $this->post($signUrl, ['signature_png' => $this->signaturePng(), 'accepted_terms' => false])->assertSessionHasErrors('accepted_terms');
        $this->post($signUrl, ['signature_png' => $this->signaturePng(), 'accepted_terms' => true, 'timezone' => 'America/Bogota'])->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame('firmado', $contract->status);
        $this->assertSame('distancia', $contract->signing_method);
        $this->assertSame('acudiente', $contract->signer_role);
        $this->assertSame('Marta Pérez', $contract->signer_name);
        $this->assertNotNull($contract->otp_verified_at);
        Notification::assertSentTo($secretaria, ContractSignedNotification::class);
        Notification::assertSentOnDemand(SignedContractCopyNotification::class);
    }

    public function test_resending_invalidates_the_previous_link_and_bad_links_are_rejected(): void
    {
        $secretaria = $this->secretariaUser();
        $enrollment = $this->enrollmentReadyForContracts();
        ContractSignature::factory()->create(['enrollment_id' => $enrollment->id]);

        $first = $this->actingAs($secretaria)->post(route('enrollments.contracts.send', $enrollment), ['via' => 'enlace'])->getSession()->get('contractLink')['url'];
        $second = $this->actingAs($secretaria)->post(route('enrollments.contracts.send', $enrollment), ['via' => 'enlace'])->getSession()->get('contractLink')['url'];
        auth()->logout();

        $this->get($first)->assertForbidden();
        $this->get($second)->assertOk();
        $this->get(route('contracts.remote.show', ['enrollment' => $enrollment, 'token' => 'falso']))->assertForbidden();
        $this->get(URL::temporarySignedRoute('contracts.remote.show', now()->subMinute(), ['enrollment' => $enrollment, 'token' => 'x']))->assertForbidden();
    }

    public function test_sending_requires_the_signer_email(): void
    {
        $enrollment = $this->enrollmentReadyForContracts(['email' => null]);
        ContractSignature::factory()->create(['enrollment_id' => $enrollment->id]);

        $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.contracts.send', $enrollment), ['via' => 'correo'])
            ->assertSessionHasErrors('contracts');
    }
}
