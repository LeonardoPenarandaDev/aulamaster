<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Http\Requests\StoreContractTemplateRequest;
use App\Http\Requests\UpdateContractTemplateRequest;
use App\Models\ContractTemplate;
use App\Models\Student;
use App\Services\ContractRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plantillas de contrato con versiones (parte 6.1 del plan de mejoras).
 * Flujo: borrador → publicado → (nueva versión) → la anterior se archiva.
 */
class ContractTemplateController extends Controller
{
    public function __construct(
        protected RecordAuditEvent $recordAudit,
    ) {}

    /**
     * Display a listing of the resource: una fila por plantilla con su
     * versión vigente y, si existe, el borrador en preparación.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', ContractTemplate::class);

        $templates = ContractTemplate::query()
            ->withCount(['signatures as signed_count' => fn ($query) => $query->where('status', 'firmado')])
            ->orderBy('name')
            ->orderByDesc('version')
            ->get();

        return Inertia::render('ContractTemplates/Index', [
            'templates' => $templates
                ->groupBy('code')
                ->map(fn ($versions) => [
                    'code' => $versions->first()->code,
                    'current' => $versions->firstWhere('status', 'publicado') ?? $versions->first(),
                    'draft' => $versions->firstWhere('status', 'borrador'),
                    'versions' => $versions->map->only(['id', 'version', 'status', 'published_at', 'signed_count'])->values(),
                ])
                ->sortBy(fn ($template) => $template['current']->name)
                ->values(),
            'types' => ContractTemplate::TYPES,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', ContractTemplate::class);

        return Inertia::render('ContractTemplates/Edit', $this->formProps(null));
    }

    /**
     * Store a new template as a draft (versión 1).
     */
    public function store(StoreContractTemplateRequest $request): RedirectResponse
    {
        $template = ContractTemplate::create([
            ...$request->validated(),
            'code' => $this->uniqueCode($request->validated('name')),
            'version' => 1,
            'status' => 'borrador',
            'created_by_id' => $request->user()->id,
        ]);

        return to_route('contract-templates.edit', $template)->with('success', 'Borrador guardado. Publícalo cuando esté listo para usarse.');
    }

    /**
     * Show a template. Los borradores se editan; las versiones publicadas o
     * archivadas solo se consultan.
     */
    public function edit(ContractTemplate $contractTemplate): Response
    {
        Gate::authorize('viewAny', ContractTemplate::class);

        return Inertia::render('ContractTemplates/Edit', $this->formProps($contractTemplate));
    }

    /**
     * Update a draft.
     */
    public function update(UpdateContractTemplateRequest $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        $contractTemplate->update($request->validated());

        return back()->with('success', 'Borrador actualizado.');
    }

    /**
     * Delete a draft that was never published.
     */
    public function destroy(ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('delete', $contractTemplate);

        $contractTemplate->delete();

        return to_route('contract-templates.index')->with('success', 'Borrador eliminado.');
    }

    /**
     * Publish a draft. La versión publicada anterior (si existe) se archiva:
     * desde ahora los contratos nuevos usan esta versión.
     */
    public function publish(ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('publish', $contractTemplate);

        DB::transaction(function () use ($contractTemplate) {
            ContractTemplate::query()
                ->where('code', $contractTemplate->code)
                ->where('status', 'publicado')
                ->update(['status' => 'archivado']);

            $contractTemplate->update(['status' => 'publicado', 'published_at' => now()]);

            $this->recordAudit->handle(
                'publicado',
                'contratos',
                $contractTemplate,
                "Plantilla {$contractTemplate->name} v{$contractTemplate->version} publicada",
            );
        });

        return to_route('contract-templates.index')->with('success', "Versión {$contractTemplate->version} publicada.");
    }

    /**
     * Create a new draft version from a published (or archived) one. Si ya
     * hay un borrador de esta plantilla, se abre ese.
     */
    public function createVersion(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('createVersion', $contractTemplate);

        $draft = ContractTemplate::query()
            ->where('code', $contractTemplate->code)
            ->where('status', 'borrador')
            ->first();

        $draft ??= ContractTemplate::create([
            ...$contractTemplate->only(['code', 'name', 'type', 'body', 'acceptance_mode', 'scope', 'requires_guardian']),
            'version' => ContractTemplate::query()->where('code', $contractTemplate->code)->max('version') + 1,
            'status' => 'borrador',
            'created_by_id' => $request->user()->id,
        ]);

        return to_route('contract-templates.edit', $draft)->with('success', "Editando la versión {$draft->version} (borrador).");
    }

    /**
     * Archive a published template: deja de asignarse a matrículas nuevas.
     */
    public function archive(ContractTemplate $contractTemplate): RedirectResponse
    {
        Gate::authorize('archive', $contractTemplate);

        $contractTemplate->update(['status' => 'archivado']);

        return to_route('contract-templates.index')->with('success', 'Plantilla archivada: ya no se asignará a matrículas nuevas.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?ContractTemplate $template): array
    {
        $renderer = app(ContractRenderer::class);

        return [
            'template' => $template,
            'types' => ContractTemplate::TYPES,
            'variables' => ContractRenderer::VARIABLES,
            'sampleValues' => [
                ...$renderer->values($this->sampleStudent()),
                'curso' => 'Inglés',
                'nivel' => 'Elementary 1',
                'precio_base' => '$1.200.000',
                'precio_final' => '$1.080.000',
                'intensidad_horaria' => '6 horas por semana',
                'fecha_inicio' => now()->locale('es')->translatedFormat('j \d\e F \d\e Y'),
                'fecha_fin_estimada' => now()->addMonths(4)->locale('es')->translatedFormat('j \d\e F \d\e Y'),
            ],
            'canEdit' => $template === null || Gate::allows('update', $template),
        ];
    }

    /**
     * Datos ficticios para la vista previa del editor.
     */
    private function sampleStudent(): Student
    {
        return new Student([
            'name' => 'Laura Gómez Ruiz',
            'document_type' => 'TI',
            'document' => '1012345678',
            'birth_date' => now()->subYears(15),
            'email' => 'laura@correo.com',
            'phone' => '+57 300 123 4567',
            'address' => 'Calle 10 # 20-30',
            'guardian_name' => 'Marta Ruiz',
            'guardian_document_type' => 'CC',
            'guardian_document' => '52123456',
            'guardian_relationship' => 'Madre',
            'guardian_email' => 'marta@correo.com',
            'guardian_phone' => '+57 310 555 1234',
        ]);
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::limit(Str::slug($name), 70, '') ?: 'contrato';
        $code = $base;
        $suffix = 2;

        while (ContractTemplate::query()->where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
