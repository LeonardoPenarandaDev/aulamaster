<?php

namespace App\Reports\Definitions;

use App\Models\Enrollment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class StudentsByLevelReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('students-by-level', 'Estudiantes por nivel', 'estudiantes');
    }

    public function headings(): array
    {
        return ['Nivel', 'Código', 'Estudiante', 'Horas', 'Progreso %', 'Estado matrícula'];
    }

    public function rows(array $filters): Collection
    {
        return Enrollment::query()
            ->with(['student:id,name,code', 'level:id,name'])
            ->when($filters['level_id'] ?? null, fn ($q, $id) => $q->where('level_id', $id))
            ->orderBy('level_id')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->level->name,
            $row->student->code,
            $row->student->name,
            "{$row->accumulated_hours}/{$row->required_hours}",
            $row->progress_percentage,
            $row->status,
        ];
    }
}
