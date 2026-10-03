<?php

namespace App\Exports;

use App\Exports\ClassSessionsTemplate\InstructionsSheet;
use App\Exports\ClassSessionsTemplate\ListsSheet;
use App\Exports\ClassSessionsTemplate\SessionsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Plantilla .xlsx para programar la semana de clases (parte 11 del plan de
 * mejoras): una hoja con ejemplos y listas desplegables, una de
 * instrucciones y una con los niveles, aulas y docentes válidos.
 */
class ClassSessionsTemplateExport implements Export, WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $lists = new ListsSheet;

        return [
            new SessionsSheet($lists),
            new InstructionsSheet,
            $lists,
        ];
    }
}
