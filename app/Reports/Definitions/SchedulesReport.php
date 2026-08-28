<?php

namespace App\Reports\Definitions;

use App\Models\ClassSession;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class SchedulesReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('schedules', 'Horarios', 'programacion_clases');
    }

    public function headings(): array
    {
        return ['Fecha', 'Hora', 'Curso / Nivel', 'Profesor', 'Aula', 'Estado'];
    }

    public function rows(array $filters): Collection
    {
        return ClassSession::query()
            ->with(['level.course:id,name', 'teacher:id,name', 'classroom:id,name'])
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->date->toDateString(),
            substr($row->start_time, 0, 5).' - '.substr($row->end_time, 0, 5),
            "{$row->level->course->name} {$row->level->name}",
            $row->teacher->name,
            $row->classroom->name,
            $row->status,
        ];
    }
}
