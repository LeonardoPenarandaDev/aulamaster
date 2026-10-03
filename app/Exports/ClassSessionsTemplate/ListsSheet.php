<?php

namespace App\Exports\ClassSessionsTemplate;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ListsSheet implements FromArray, WithTitle
{
    /**
     * @var array{levels: list<string>, classrooms: list<string>, teachers: list<string>}|null
     */
    private ?array $lists = null;

    /**
     * @return array{levels: list<string>, classrooms: list<string>, teachers: list<string>}
     */
    public function lists(): array
    {
        return $this->lists ??= [
            'levels' => Level::query()->where('status', 'activo')->orderBy('code')->pluck('code')->all(),
            'classrooms' => Classroom::query()->orderBy('name')->pluck('name')->all(),
            'teachers' => Teacher::query()->where('status', 'activo')->whereNotNull('email')->orderBy('name')->pluck('email')->all(),
        ];
    }

    public function title(): string
    {
        return 'Listas';
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    public function array(): array
    {
        $lists = $this->lists();
        $rows = [['Niveles (código)', 'Aulas', 'Docentes (correo)', 'Días', 'Modalidad']];
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $length = max(count($lists['levels']), count($lists['classrooms']), count($lists['teachers']), count($days));

        for ($i = 0; $i < $length; $i++) {
            $rows[] = [
                $lists['levels'][$i] ?? null,
                $lists['classrooms'][$i] ?? null,
                $lists['teachers'][$i] ?? null,
                $days[$i] ?? null,
                ['presencial', 'virtual'][$i] ?? null,
            ];
        }

        return $rows;
    }
}
