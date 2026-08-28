<?php

namespace App\Reports\Definitions;

use App\Models\Enrollment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class StudentsByCourseReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('students-by-course', 'Estudiantes por curso', 'estudiantes');
    }

    public function headings(): array
    {
        return ['Curso', 'Nivel', 'Código', 'Estudiante', 'Estado matrícula'];
    }

    public function rows(array $filters): Collection
    {
        return Enrollment::query()
            ->with(['student:id,name,code', 'level.course:id,name'])
            ->when($filters['course_id'] ?? null, fn ($q, $id) => $q->whereHas('level', fn ($lq) => $lq->where('course_id', $id)))
            ->orderBy('level_id')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->level->course->name, $row->level->name, $row->student->code, $row->student->name, $row->status];
    }
}
