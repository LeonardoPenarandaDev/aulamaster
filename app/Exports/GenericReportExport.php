<?php

namespace App\Exports;

use App\Reports\ReportDefinition;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exportador Excel/CSV genérico: cualquier ReportDefinition puede
 * exportarse sin necesitar su propia clase de exportación.
 */
class GenericReportExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected ReportDefinition $report,
        protected array $filters,
    ) {}

    public function headings(): array
    {
        return $this->report->headings();
    }

    public function collection(): Collection
    {
        return $this->report->rows($this->filters)->map(fn ($row) => $this->report->toRow($row));
    }
}
