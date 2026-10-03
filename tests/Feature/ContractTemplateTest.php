<?php

namespace Tests\Feature;

use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Services\ContractRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 6.1 del plan de mejoras: plantillas de contrato versionadas. Solo
 * el admin las gestiona, y una plantilla publicada no se edita.
 */
class ContractTemplateTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function templateData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Contrato de matrícula',
            'type' => 'matricula',
            'body' => 'Yo, {{firmante.nombre}}, acepto las condiciones.',
            'acceptance_mode' => 'obligatorio',
            'scope' => 'matricula',
            'requires_guardian' => false,
        ], $overrides);
    }

    public function test_admin_creates_a_draft_and_publishes_it(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('contract-templates.store'), $this->templateData())->assertSessionHasNoErrors();

        $template = ContractTemplate::query()->firstOrFail();
        $this->assertSame('borrador', $template->status);
        $this->assertSame(1, $template->version);
        $this->assertSame('contrato-de-matricula', $template->code);

        $this->actingAs($admin)->post(route('contract-templates.publish', $template))->assertRedirect(route('contract-templates.index'));

        $this->assertSame('publicado', $template->fresh()->status);
        $this->assertNotNull($template->fresh()->published_at);
    }

    public function test_published_template_cannot_be_edited_but_a_new_version_can(): void
    {
        $admin = $this->adminUser();
        $v1 = ContractTemplate::factory()->published()->create(['code' => 'datos', 'body' => 'Texto original']);

        $this->actingAs($admin)
            ->put(route('contract-templates.update', $v1), $this->templateData(['body' => 'Texto cambiado']))
            ->assertForbidden();

        $this->actingAs($admin)->post(route('contract-templates.new-version', $v1));
        $v2 = ContractTemplate::query()->where('code', 'datos')->where('version', 2)->firstOrFail();
        $this->assertSame('borrador', $v2->status);
        $this->assertSame('Texto original', $v2->body);

        $this->actingAs($admin)->put(route('contract-templates.update', $v2), $this->templateData(['body' => 'Texto nuevo']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('contract-templates.publish', $v2));

        $this->assertSame('archivado', $v1->fresh()->status);
        $this->assertSame('Texto original', $v1->fresh()->body);
        $this->assertSame('publicado', $v2->fresh()->status);
        $this->assertSame('Texto nuevo', $v2->fresh()->body);
    }

    public function test_requesting_a_new_version_twice_reuses_the_existing_draft(): void
    {
        $admin = $this->adminUser();
        $v1 = ContractTemplate::factory()->published()->create(['code' => 'reglamento']);

        $this->actingAs($admin)->post(route('contract-templates.new-version', $v1));
        $this->actingAs($admin)->post(route('contract-templates.new-version', $v1));

        $this->assertSame(2, ContractTemplate::query()->where('code', 'reglamento')->count());
    }

    public function test_only_drafts_can_be_deleted(): void
    {
        $admin = $this->adminUser();
        $draft = ContractTemplate::factory()->create();
        $published = ContractTemplate::factory()->published()->create();

        $this->actingAs($admin)->delete(route('contract-templates.destroy', $draft))->assertRedirect();
        $this->actingAs($admin)->delete(route('contract-templates.destroy', $published))->assertForbidden();

        $this->assertModelMissing($draft);
        $this->assertModelExists($published);
    }

    public function test_other_roles_cannot_manage_templates(): void
    {
        foreach ([$this->secretariaUser(), $this->cajeroUser(), $this->coordinadorUser()] as $user) {
            $this->actingAs($user)->get(route('contract-templates.index'))->assertForbidden();
            $this->actingAs($user)->post(route('contract-templates.store'), $this->templateData())->assertForbidden();
        }
    }

    public function test_renderer_fills_student_guardian_and_enrollment_variables(): void
    {
        InstitutionSetting::current()->update(['name' => 'Instituto Horizonte']);
        $student = Student::factory()->create([
            'name' => 'Luis Pérez',
            'document_type' => 'TI',
            'document' => '1098',
            'birth_date' => now()->subYears(12),
            'guardian_name' => 'Marta Pérez',
            'guardian_document_type' => 'CC',
            'guardian_document' => '5212',
        ]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'final_price' => 1080000]);

        $renderer = app(ContractRenderer::class);
        $text = $renderer->render(
            '{{firmante.nombre}} ({{firmante.documento}}), acudiente de {{alumno.nombre}} ({{alumno.documento}}), pagará {{precio_final}} a {{institucion.nombre}}. {{desconocida}}',
            $renderer->values($student, $enrollment, 'Descuento por hermanos.'),
        );

        $this->assertStringStartsWith('Marta Pérez (CC 5212), acudiente de Luis Pérez (TI 1098), pagará $1.080.000 a Instituto Horizonte. {{desconocida}}', $text);
        $this->assertStringEndsWith("CLÁUSULAS ESPECIALES\n\nDescuento por hermanos.", $text);
    }
}
