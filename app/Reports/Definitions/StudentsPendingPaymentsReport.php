<?php

namespace App\Reports\Definitions;

use App\Models\Payment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class StudentsPendingPaymentsReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('students-pending-payments', 'Estudiantes con pagos pendientes', 'pagos');
    }

    public function headings(): array
    {
        return ['Estudiante', 'Concepto', 'Valor final', 'Estado', 'Fecha de pago'];
    }

    public function rows(array $filters): Collection
    {
        return Payment::query()
            ->with('student:id,name,code')
            ->whereIn('status', ['pendiente', 'vencido'])
            ->orderBy('student_id')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->student->name, $row->concept, $row->final_amount, $row->status, $row->paid_at?->toDateString() ?? '—'];
    }
}
