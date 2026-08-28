<?php

namespace App\Reports\Definitions;

use App\Models\Student;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class StudentListReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('students', 'Listado de estudiantes', 'estudiantes');
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'Documento', 'Correo', 'Teléfono', 'Estado'];
    }

    public function rows(array $filters): Collection
    {
        return Student::query()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->code, $row->name, $row->document, $row->email, $row->phone, $row->status];
    }
}
