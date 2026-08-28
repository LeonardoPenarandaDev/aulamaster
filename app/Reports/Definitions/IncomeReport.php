<?php

namespace App\Reports\Definitions;

use App\Models\Payment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class IncomeReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('income', 'Ingresos', 'pagos');
    }

    public function headings(): array
    {
        return ['Fecha de pago', 'Estudiante', 'Concepto', 'Valor final', 'Método de pago'];
    }

    public function rows(array $filters): Collection
    {
        return Payment::query()
            ->with('student:id,name')
            ->where('status', 'pagado')
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('paid_at', '<=', $date))
            ->orderByDesc('paid_at')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->paid_at?->toDateString(), $row->student->name, $row->concept, $row->final_amount, $row->payment_method ?? '—'];
    }
}
