<?php

namespace App\Actions\Scheduling;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\Teacher;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Lee y valida el archivo de la semana de clases (parte 11 del plan de
 * mejoras). Cada fila queda como "ok", "sin_docente" o "error", con los
 * choques de aula y de docente revisados contra lo ya programado y contra
 * las demás filas del mismo archivo.
 */
class ParseClassSessionImport
{
    /**
     * Columnas de la plantilla, en orden.
     *
     * @var list<string>
     */
    public const COLUMNS = ['dia', 'hora_inicio', 'hora_fin', 'nivel', 'aula', 'modalidad', 'docente', 'enlace_virtual', 'notas'];

    /**
     * @var array<string, int>
     */
    public const DAYS = ['lunes' => 0, 'martes' => 1, 'miercoles' => 2, 'jueves' => 3, 'viernes' => 4, 'sabado' => 5, 'domingo' => 6];

    public const MAX_ROWS = 500;

    public function __construct(
        protected CheckClassSessionConflicts $checkConflicts,
    ) {}

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @return list<array{line: int, raw: array<string, string>, status: string, errors: list<string>, data: array<string, mixed>|null, dates: list<string>}>
     */
    public function handle(Collection $rows, CarbonInterface $weekStart, int $repeatWeeks): array
    {
        $levels = Level::query()->get(['id', 'code', 'name'])->keyBy(fn (Level $level) => Str::lower($level->code));
        $classrooms = Classroom::query()->get(['id', 'name'])->keyBy(fn (Classroom $classroom) => Str::lower($classroom->name));
        $teachers = Teacher::query()->get(['id', 'name', 'email', 'document']);

        $parsed = [];

        foreach ($rows->values() as $index => $row) {
            $raw = collect(self::COLUMNS)->mapWithKeys(fn (string $column) => [$column => $this->cell($row->get($column))])->all();

            if (collect($raw)->filter()->isEmpty()) {
                continue;
            }

            $parsed[] = $this->parseRow($index + 2, $raw, $row, $weekStart, $repeatWeeks, $levels, $classrooms, $teachers);
        }

        return $this->checkConflictsBetweenRows($parsed);
    }

    /**
     * @param  array<string, string>  $raw
     * @return array{line: int, raw: array<string, string>, status: string, errors: list<string>, data: array<string, mixed>|null, dates: list<string>}
     */
    private function parseRow(int $line, array $raw, Collection $row, CarbonInterface $weekStart, int $repeatWeeks, Collection $levels, Collection $classrooms, Collection $teachers): array
    {
        $errors = [];

        $dayOffset = $this->dayOffset($raw['dia']);
        if ($dayOffset === null) {
            $errors[] = 'Día no válido (usa lunes a domingo).';
        }

        $start = $this->time($row->get('hora_inicio'));
        $end = $this->time($row->get('hora_fin'));
        if (! $start || ! $end) {
            $errors[] = 'Hora no válida (usa el formato 08:00).';
        } elseif ($start >= $end) {
            $errors[] = 'La hora de fin debe ser posterior a la de inicio.';
        }

        $level = $levels->get(Str::lower($raw['nivel']));
        if (! $level) {
            $errors[] = $raw['nivel'] === '' ? 'Falta el código del nivel.' : "No existe el nivel con código «{$raw['nivel']}».";
        }

        $classroom = $classrooms->get(Str::lower($raw['aula']));
        if (! $classroom) {
            $errors[] = $raw['aula'] === '' ? 'Falta el aula.' : "No existe el aula «{$raw['aula']}».";
        }

        $modality = Str::lower($raw['modalidad'] ?: 'presencial');
        if (! in_array($modality, ['presencial', 'virtual'], true)) {
            $errors[] = 'Modalidad no válida (presencial o virtual).';
        }

        $teacher = null;
        if ($raw['docente'] !== '') {
            $teacher = $teachers->first(fn (Teacher $item) => Str::lower((string) $item->email) === Str::lower($raw['docente']) || (string) $item->document === $raw['docente']);
            if (! $teacher) {
                $errors[] = "No existe el docente «{$raw['docente']}» (usa su correo o documento).";
            }
        }

        if ($raw['enlace_virtual'] !== '' && ! filter_var($raw['enlace_virtual'], FILTER_VALIDATE_URL)) {
            $errors[] = 'El enlace virtual no es una URL válida.';
        }

        $dates = $dayOffset === null ? [] : collect(range(0, $repeatWeeks - 1))
            ->map(fn (int $week) => $weekStart->copy()->addDays($week * 7 + $dayOffset)->toDateString())
            ->all();

        if ($errors === []) {
            foreach ($dates as $date) {
                $conflicts = $this->checkConflicts->handle($classroom->id, $teacher?->id, $date, $start, $end, modality: $modality);

                foreach ($conflicts as $field => $message) {
                    $errors[] = ($field === 'classroom_id' ? 'Choque de aula' : 'Choque de docente').' el '.date('d/m', strtotime($date)).': ya hay una clase programada a esa hora.';
                }
            }
        }

        return [
            'line' => $line,
            'raw' => $raw,
            'status' => $errors !== [] ? 'error' : ($teacher ? 'ok' : 'sin_docente'),
            'errors' => array_values(array_unique($errors)),
            'data' => $errors !== [] ? null : [
                'level_id' => $level->id,
                'level' => $level->name,
                'classroom_id' => $classroom->id,
                'classroom' => $classroom->name,
                'teacher_id' => $teacher?->id,
                'teacher' => $teacher?->name,
                'modality' => $modality,
                'meeting_url' => $raw['enlace_virtual'] ?: null,
                'notes' => $raw['notas'] ?: null,
                'start_time' => $start,
                'end_time' => $end,
            ],
            'dates' => $dates,
        ];
    }

    /**
     * Choques dentro del mismo archivo: dos filas en la misma aula
     * presencial o con el mismo docente, el mismo día y a la misma hora.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function checkConflictsBetweenRows(array $rows): array
    {
        foreach ($rows as $i => $row) {
            foreach ($rows as $j => $other) {
                if ($j <= $i || ! $row['data'] || ! $other['data'] || array_intersect($row['dates'], $other['dates']) === []) {
                    continue;
                }

                $overlaps = $row['data']['start_time'] < $other['data']['end_time'] && $other['data']['start_time'] < $row['data']['end_time'];
                if (! $overlaps) {
                    continue;
                }

                $sameClassroom = $row['data']['modality'] === 'presencial' && $other['data']['modality'] === 'presencial'
                    && $row['data']['classroom_id'] === $other['data']['classroom_id'];
                $sameTeacher = $row['data']['teacher_id'] && $row['data']['teacher_id'] === $other['data']['teacher_id'];

                if ($sameClassroom || $sameTeacher) {
                    $message = ($sameClassroom ? 'Choque de aula' : 'Choque de docente')." con la fila {$row['line']} del archivo.";
                    $rows[$j]['errors'][] = $message;
                    $rows[$j]['status'] = 'error';
                    $rows[$j]['data'] = null;
                }
            }
        }

        return $rows;
    }

    private function cell(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function dayOffset(string $value): ?int
    {
        $day = Str::of($value)->lower()->ascii()->trim()->toString();

        if (is_numeric($day) && (int) $day >= 1 && (int) $day <= 7) {
            return (int) $day - 1;
        }

        return self::DAYS[$day] ?? null;
    }

    /**
     * Excel guarda las horas como fracción del día (0,333… = 08:00); en CSV
     * llegan como texto ("8:00", "08:00", "2:30 pm").
     */
    private function time(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value < 1) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i');
        }

        $timestamp = strtotime(str_replace('.', '', Str::lower((string) $value)));

        return $timestamp !== false ? date('H:i', $timestamp) : null;
    }
}
