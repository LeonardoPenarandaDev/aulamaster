<?php

namespace App\Reports\Definitions;

use App\Models\Classroom;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class ClassroomOccupancyReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('classroom-occupancy', 'Ocupación de aulas', 'aulas');
    }

    public function headings(): array
    {
        return ['Aula', 'Capacidad', 'Clases programadas', 'Clases dictadas', 'Clases canceladas'];
    }

    public function rows(array $filters): Collection
    {
        return Classroom::query()
            ->withCount([
                'classSessions as programada_count' => fn ($q) => $q->where('status', 'programada'),
                'classSessions as dictada_count' => fn ($q) => $q->where('status', 'dictada'),
                'classSessions as cancelada_count' => fn ($q) => $q->where('status', 'cancelada'),
            ])
            ->orderBy('name')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->name, $row->capacity, $row->programada_count, $row->dictada_count, $row->cancelada_count];
    }
}
