<?php

namespace App\Actions\Contracts;

use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\ContractRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateEnrollmentContracts
{
    public function __construct(
        protected ContractRenderer $renderer,
    ) {}

    /**
     * Plantillas publicadas que le corresponden a la matrícula y que todavía
     * no tienen un contrato vigente (parte 6.3 del plan de mejoras): se
     * omiten las "por alumno" que el estudiante ya firmó y las que ya tienen
     * un contrato abierto o firmado en esta matrícula.
     *
     * @return Collection<int, ContractTemplate>
     */
    public function pendingTemplates(Enrollment $enrollment): Collection
    {
        $student = $enrollment->student;

        $existing = ContractSignature::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'anulado')
            ->with('template:id,code')
            ->get(['id', 'enrollment_id', 'contract_template_id', 'status']);

        return ContractTemplate::query()
            ->published()
            ->orderBy('name')
            ->get()
            ->filter(fn (ContractTemplate $template) => $template->appliesTo($student))
            ->reject(fn (ContractTemplate $template) => $existing->contains(fn (ContractSignature $signature) => $signature->template->code === $template->code
                && ($template->scope === 'alumno' || $signature->enrollment_id === $enrollment->id)
            ))
            ->values();
    }

    /**
     * Genera los contratos con el texto ya reemplazado. Una vez generados
     * no se modifican: para cambiarlos se anulan y se generan de nuevo.
     *
     * @param  array<int, string|null>  $specialClauses  cláusulas especiales por id de plantilla
     * @return Collection<int, ContractSignature>
     *
     * @throws ValidationException
     */
    public function handle(Enrollment $enrollment, User $generatedBy, array $specialClauses = []): Collection
    {
        $enrollment->loadMissing(['student', 'level.course']);
        $student = $enrollment->student;

        if ($missing = $student->missingContractData()) {
            throw ValidationException::withMessages([
                'contracts' => 'Datos incompletos del estudiante: falta '.implode(', ', $missing).'.',
            ]);
        }

        $templates = $this->pendingTemplates($enrollment);

        if ($templates->isEmpty()) {
            throw ValidationException::withMessages([
                'contracts' => 'No hay contratos pendientes por generar para esta matrícula.',
            ]);
        }

        return DB::transaction(fn () => $templates->map(function (ContractTemplate $template) use ($enrollment, $student, $generatedBy, $specialClauses) {
            $clauses = trim((string) ($specialClauses[$template->id] ?? '')) ?: null;
            $body = $this->renderer->render($template->body, $this->renderer->values($student, $enrollment, $clauses));

            return ContractSignature::create([
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'contract_template_id' => $template->id,
                'template_version' => $template->version,
                'status' => 'pendiente',
                'special_clauses' => $clauses,
                'rendered_body' => $body,
                'content_hash' => hash('sha256', $body),
                'generated_by_id' => $generatedBy->id,
            ]);
        }));
    }
}
