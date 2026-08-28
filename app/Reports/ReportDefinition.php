<?php

namespace App\Reports;

use Illuminate\Support\Collection;

/**
 * Contrato común para los reportes de la sección 45 del plan. Cada reporte
 * concreto vive en app/Reports/Definitions y sabe: cómo se llama, a qué
 * módulo pertenece, qué columnas expone y cómo convertir sus registros en
 * filas de texto plano — lo mismo se usa para la vista en pantalla y para
 * las exportaciones a PDF/Excel/CSV, así que no hay lógica duplicada entre
 * "ver el reporte" y "exportarlo".
 */
interface ReportDefinition
{
    public function key(): string;

    public function label(): string;

    public function module(): string;

    /**
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, mixed>
     */
    public function rows(array $filters): Collection;

    /**
     * @param  mixed  $row
     * @return array<int, string|int|float|null>
     */
    public function toRow($row): array;
}
