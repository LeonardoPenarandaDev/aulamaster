<?php

namespace App\Reports\Definitions;

use App\Models\EvaluationResult;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class EvaluationsFailedReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('evaluations-failed', 'Evaluaciones reprobadas', 'evaluaciones');
    }

    public function headings(): array
    {
        return ['Fecha', 'Estudiante', 'Nivel', 'Evaluación', 'Intento', 'Nota'];
    }

    public function rows(array $filters): Collection
    {
        return EvaluationResult::query()
            ->with(['enrollment.student:id,name', 'enrollment.level:id,name', 'evaluation:id,name'])
            ->where('result', 'reprobado')
            ->when($filters['level_id'] ?? null, fn ($q, $id) => $q->whereHas('enrollment', fn ($eq) => $eq->where('level_id', $id)))
            ->orderByDesc('evaluated_at')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->evaluated_at->toDateString(),
            $row->enrollment->student->name,
            $row->enrollment->level->name,
            $row->evaluation->name,
            $row->attempt_number,
            $row->grade,
        ];
    }
}
