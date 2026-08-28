<?php

namespace App\Reports\Definitions;

use App\Models\Attendance;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class AttendanceByDateReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('attendance-by-date', 'Asistencia por fecha', 'asistencia');
    }

    public function headings(): array
    {
        return ['Fecha', 'Estudiante', 'Nivel', 'Profesor', 'Estado'];
    }

    public function rows(array $filters): Collection
    {
        return Attendance::query()
            ->with(['enrollment.student:id,name,code', 'enrollment.level:id,name', 'teacher:id,name'])
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('class_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('class_date', '<=', $date))
            ->orderBy('class_date')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->class_date->toDateString(),
            $row->enrollment->student->name,
            $row->enrollment->level->name,
            $row->teacher->name,
            $row->effective_status,
        ];
    }
}
