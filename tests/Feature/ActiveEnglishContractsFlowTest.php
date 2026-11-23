<?php

namespace Tests\Feature;

use App\Models\ContractSignature;
use App\Models\Payment;
use Database\Seeders\ActiveEnglishContractTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithContracts;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Flujo completo con las plantillas de Active English: se generan los
 * contratos de una matrícula (incluida la ficha de registro y la
 * autorización de uso de imagen) y se firman en la oficina.
 */
class ActiveEnglishContractsFlowTest extends TestCase
{
    use InteractsWithContracts, InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        $this->seed(ActiveEnglishContractTemplatesSeeder::class);
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

    public function test_enrollment_generates_the_registration_form_and_image_authorization(): void
    {
        $enrollment = $this->enrollmentReadyForContracts(['name' => 'Ana Gómez', 'address' => 'Calle 10 # 5-20']);

        $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.contracts.store', $enrollment))
            ->assertSessionHasNoErrors();

        $contracts = ContractSignature::query()->with('template')->where('enrollment_id', $enrollment->id)->get()->keyBy('template.code');

        $this->assertEqualsCanonicalizing(
            ['active-contrato-matricula', 'active-pagare', 'active-centrales-riesgo', 'active-ficha-matricula', 'active-uso-imagen'],
            $contracts->keys()->all(),
        );

        $form = $contracts['active-ficha-matricula']->rendered_body;
        $this->assertStringContainsString("FICHA DE REGISTRO DE MATRÍCULA No. {$enrollment->id}", $form);
        $this->assertStringContainsString('Nombre completo: Ana Gómez', $form);
        $this->assertStringContainsString('Dirección: Calle 10 # 5-20', $form);
        $this->assertStringNotContainsString('{{', $form);

        $this->assertStringContainsString('AUTORIZO a ACTIVE ENGLISH INSTITUTE SAS', $contracts['active-uso-imagen']->rendered_body);
        $this->assertSame('opcional', $contracts['active-uso-imagen']->template->acceptance_mode);
    }

    public function test_declining_the_image_authorization_still_activates_the_enrollment(): void
    {
        $enrollment = $this->enrollmentReadyForContracts();
        $secretaria = $this->secretariaUser();
        Payment::factory()->create(['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'status' => 'pagado']);

        $this->actingAs($secretaria)->post(route('enrollments.contracts.store', $enrollment));

        $contracts = ContractSignature::query()->with('template')->where('enrollment_id', $enrollment->id)->get();

        foreach ($contracts as $contract) {
            $data = $contract->template->code === 'active-uso-imagen'
                ? $this->officeData(['decision' => 'no_acepta', 'id_front' => null])
                : $this->officeData(['id_front' => null]);

            if ($contract->is($contracts->first())) {
                $data['id_front'] = UploadedFile::fake()->image('anverso.jpg');
            }

            $this->actingAs($secretaria)
                ->post(route('contract-signatures.sign', $contract), $data)
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, ContractSignature::query()->where('status', 'firmado')->count());
        $this->assertFalse($enrollment->student->fresh()->image_consent);
        $this->assertSame('activa', $enrollment->fresh()->status);
    }
}
