<?php

namespace App\Reports\Definitions;

use App\Models\Attendance;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class AttendanceByTeacherReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('attendance-by-teacher', 'Asistencia por profesor', 'asistencia');
    }

    public function headings(): array
    {
        return ['Profesor', 'Fecha', 'Estudiante', 'Nivel', 'Estado'];
    }

    public function rows(array $filters): Collection
    {
        return Attendance::query()
            ->with(['enrollment.student:id,name', 'enrollment.level:id,name', 'teacher:id,name'])
            ->when($filters['teacher_id'] ?? null, fn ($q, $id) => $q->where('teacher_id', $id))
            ->orderBy('teacher_id')
            ->orderBy('class_date')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->teacher->name,
            $row->class_date->toDateString(),
            $row->enrollment->student->name,
            $row->enrollment->level->name,
            $row->effective_status,
        ];
    }
}
