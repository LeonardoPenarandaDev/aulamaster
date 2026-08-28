<?php

namespace App\Reports\Definitions;

use App\Models\Attendance;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class AttendanceByClassroomReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('attendance-by-classroom', 'Asistencia por aula', 'asistencia');
    }

    public function headings(): array
    {
        return ['Aula', 'Fecha', 'Estudiante', 'Profesor', 'Estado'];
    }

    public function rows(array $filters): Collection
    {
        return Attendance::query()
            ->with(['classSession.classroom:id,name', 'enrollment.student:id,name', 'teacher:id,name'])
            ->when($filters['classroom_id'] ?? null, fn ($q, $id) => $q->whereHas('classSession', fn ($sq) => $sq->where('classroom_id', $id)))
            ->orderBy('class_date')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->classSession->classroom->name,
            $row->class_date->toDateString(),
            $row->enrollment->student->name,
            $row->teacher->name,
            $row->effective_status,
        ];
    }
}
