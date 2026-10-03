<?php

namespace App\Exports\ClassSessionsTemplate;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class InstructionsSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Cómo llenar la hoja "Clases"'],
            [''],
            ['dia', 'Día de la semana: lunes a domingo. Al subir el archivo eliges la semana.'],
            ['hora_inicio / hora_fin', 'Formato 24 horas, por ejemplo 08:00 y 10:00.'],
            ['nivel', 'Código del nivel (ver la hoja "Listas").'],
            ['aula', 'Nombre del aula tal como está en el sistema.'],
            ['modalidad', 'presencial o virtual.'],
            ['docente', 'Correo o documento del docente. Puede quedar vacío: la clase queda "Sin docente" y se asigna después.'],
            ['enlace_virtual', 'Opcional. Enlace de la reunión para clases virtuales.'],
            ['notas', 'Opcional.'],
            [''],
            ['Antes de crear las clases verás una vista previa con las filas correctas, las que no tienen docente y las que tienen errores (incluidos los choques de aula o docente).'],
        ];
    }
}
