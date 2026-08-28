<?php

namespace App\Reports\Definitions;

use App\Models\Enrollment;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class PromotionsUsedReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('promotions-used', 'Promociones utilizadas', 'promociones');
    }

    public function headings(): array
    {
        return ['Promoción', 'Estudiante', 'Nivel', 'Descuento aplicado', 'Fecha de matrícula'];
    }

    public function rows(array $filters): Collection
    {
        return Enrollment::query()
            ->with(['promotion:id,name', 'student:id,name', 'level:id,name'])
            ->whereNotNull('promotion_id')
            ->orderByDesc('enrolled_at')
            ->get();
    }

    public function toRow($row): array
    {
        return [$row->promotion->name, $row->student->name, $row->level->name, $row->promotion_discount, $row->enrolled_at->toDateString()];
    }
}
