<?php

namespace App\Reports\Definitions;

use App\Models\EvaluationResult;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class RecoveriesPaidReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('recoveries-paid', 'Recuperaciones pagadas', 'recuperaciones');
    }

    public function headings(): array
    {
        return ['Fecha', 'Estudiante', 'Evaluación', 'Nº recuperación', 'Costo', 'Estado del pago'];
    }

    public function rows(array $filters): Collection
    {
        return EvaluationResult::query()
            ->with(['enrollment.student:id,name', 'evaluation:id,name'])
            ->where('is_recovery', true)
            ->where('cost', '>', 0)
            ->orderByDesc('evaluated_at')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->evaluated_at->toDateString(),
            $row->enrollment->student->name,
            $row->evaluation->name,
            $row->attempt_number - 1,
            $row->cost,
            $row->payment_status,
        ];
    }
}
