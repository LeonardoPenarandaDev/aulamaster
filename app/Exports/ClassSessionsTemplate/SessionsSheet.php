<?php

namespace App\Exports\ClassSessionsTemplate;

use App\Actions\Scheduling\ParseClassSessionImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class SessionsSheet implements FromArray, WithEvents, WithTitle
{
    public function __construct(
        protected ListsSheet $lists,
    ) {}

    public function title(): string
    {
        return 'Clases';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        $lists = $this->lists->lists();

        return [
            ParseClassSessionImport::COLUMNS,
            ['lunes', '08:00', '10:00', $lists['levels'][0] ?? 'NIV-001', $lists['classrooms'][0] ?? 'Aula 1', 'presencial', $lists['teachers'][0] ?? '', '', ''],
            ['miercoles', '18:00', '20:00', $lists['levels'][0] ?? 'NIV-001', $lists['classrooms'][0] ?? 'Aula 1', 'virtual', '', 'https://meet.google.com/abc-defg-hij', 'Docente por confirmar'],
        ];
    }

    /**
     * Listas desplegables (filas 2 a 500) que leen la hoja "Listas".
     *
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lists = $this->lists->lists();
                $sheet = $event->sheet->getDelegate();
                $ranges = [
                    'A' => '=Listas!$D$2:$D$8',
                    'D' => '=Listas!$A$2:$A$'.max(2, count($lists['levels']) + 1),
                    'E' => '=Listas!$B$2:$B$'.max(2, count($lists['classrooms']) + 1),
                    'F' => '=Listas!$E$2:$E$3',
                    'G' => '=Listas!$C$2:$C$'.max(2, count($lists['teachers']) + 1),
                ];

                foreach ($ranges as $column => $formula) {
                    $validation = $sheet->getCell("{$column}2")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setAllowBlank(true)
                        ->setShowDropDown(true)
                        ->setShowErrorMessage($column !== 'G')
                        ->setFormula1($formula);
                    $sheet->setDataValidation("{$column}2:{$column}500", clone $validation);
                }

                foreach (range('A', 'I') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                $sheet->getStyle('A1:I1')->getFont()->setBold(true);
                $sheet->freezePane('A2');
            },
        ];
    }
}
