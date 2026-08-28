<?php

namespace App\Reports\Definitions;

use App\Models\Enrollment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class AccumulatedHoursReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('accumulated-hours', 'Horas acumuladas', 'asistencia');
    }

    public function headings(): array
    {
        return ['Estudiante', 'Nivel', 'Horas acumuladas', 'Horas requeridas', 'Pendientes', 'Progreso %'];
    }

    public function rows(array $filters): Collection
    {
        return Enrollment::query()
            ->with(['student:id,name', 'level:id,name'])
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->when($filters['level_id'] ?? null, fn ($q, $id) => $q->where('level_id', $id))
            ->orderBy('student_id')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->student->name,
            $row->level->name,
            $row->accumulated_hours,
            $row->required_hours,
            $row->pending_hours,
            $row->progress_percentage,
        ];
    }
}
