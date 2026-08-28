<?php

namespace App\Reports\Definitions;

use App\Actions\Evaluations\CheckEvaluationEligibility;
use App\Models\Enrollment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class StudentsNearEvaluationReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('students-near-evaluation', 'Estudiantes próximos a evaluación', 'evaluaciones');
    }

    public function headings(): array
    {
        return ['Estudiante', 'Nivel', 'Horas', 'Evaluaciones pendientes de presentar'];
    }

    public function rows(array $filters): Collection
    {
        $checkEligibility = app(CheckEvaluationEligibility::class);

        return Enrollment::query()
            ->with(['student:id,name', 'level:id,name', 'level.evaluations' => fn ($q) => $q->where('status', 'activo')])
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->get()
            ->filter(fn (Enrollment $enrollment) => $checkEligibility->handle($enrollment) === [])
            ->map(function (Enrollment $enrollment) {
                $presented = $enrollment->evaluationResults()->pluck('evaluation_id')->unique();
                $pending = $enrollment->level->evaluations->whereNotIn('id', $presented);

                return [$enrollment, $pending->pluck('name')->implode(', ')];
            })
            ->filter(fn ($pair) => $pair[1] !== '')
            ->values();
    }

    public function toRow($row): array
    {
        [$enrollment, $pendingNames] = $row;

        return [$enrollment->student->name, $enrollment->level->name, "{$enrollment->accumulated_hours}/{$enrollment->required_hours}", $pendingNames];
    }
}
