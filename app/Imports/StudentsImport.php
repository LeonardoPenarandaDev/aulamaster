<?php

namespace App\Imports;

use App\Http\Requests\Concerns\ValidatesStudentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Registro masivo de estudiantes desde CSV/Excel. Las cabeceras se
 * normalizan a slug (p. ej. "Teléfono" → "telefono"). Todo o nada: si
 * alguna fila es inválida no se crea ningún estudiante y se reporta cada
 * error con su número de fila.
 */
class StudentsImport implements ToCollection, WithHeadingRow
{
    use ValidatesStudentGuardian;

    public const MAX_ROWS = 1000;

    /**
     * Columnas de la plantilla, en el orden en que se descargan.
     *
     * @var list<string>
     */
    public const COLUMNS = [
        'codigo',
        'nombre',
        'tipo_documento',
        'documento',
        'fecha_nacimiento',
        'correo',
        'telefono',
        'direccion',
        'estado',
        'contrasena',
        'acudiente_nombre',
        'acudiente_tipo_documento',
        'acudiente_documento',
        'acudiente_parentesco',
        'acudiente_correo',
        'acudiente_telefono',
    ];

    public int $createdCount = 0;

    public int $portalAccessCount = 0;

    public function collection(Collection $rows): void
    {
        $students = $rows
            ->map(fn (Collection $row, int $index): array => ['line' => $index + 2, 'data' => $this->normalize($row)])
            ->reject(fn (array $row): bool => collect($row['data'])->filter()->isEmpty())
            ->values();

        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['file' => 'El archivo no contiene estudiantes.']);
        }

        if ($students->count() > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'El archivo supera el máximo de '.self::MAX_ROWS.' estudiantes por importación.']);
        }

        $this->validateRows($students);

        foreach ($students as $row) {
            $this->createStudent($row['data']);
        }
    }

    /**
     * @return array<string, ?string>
     */
    private function normalize(Collection $row): array
    {
        $value = function (string $column) use ($row): ?string {
            $cell = trim((string) $row->get($column));

            return $cell === '' ? null : $cell;
        };

        return [
            'code' => $value('codigo'),
            'name' => $value('nombre'),
            'document_type' => $value('tipo_documento') ? Str::upper($value('tipo_documento')) : null,
            'document' => $value('documento'),
            'birth_date' => $this->normalizeDate($row->get('fecha_nacimiento')),
            'email' => $value('correo') ? Str::lower($value('correo')) : null,
            'phone' => $value('telefono'),
            'address' => $value('direccion'),
            'status' => Str::lower($value('estado') ?? 'activo'),
            'password' => $value('contrasena'),
            'guardian_name' => $value('acudiente_nombre'),
            'guardian_document_type' => $value('acudiente_tipo_documento') ? Str::upper($value('acudiente_tipo_documento')) : null,
            'guardian_document' => $value('acudiente_documento'),
            'guardian_relationship' => $value('acudiente_parentesco'),
            'guardian_email' => $value('acudiente_correo') ? Str::lower($value('acudiente_correo')) : null,
            'guardian_phone' => $value('acudiente_telefono'),
        ];
    }

    /**
     * Excel guarda las fechas como número de serie; en CSV llegan como texto
     * (AAAA-MM-DD o DD/MM/AAAA). Si no se reconoce, se deja tal cual para
     * que la validación reporte el error con su número de fila.
     */
    private function normalizeDate(mixed $cell): ?string
    {
        if ($cell === null || trim((string) $cell) === '') {
            return null;
        }

        if (is_numeric($cell)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $cell)->format('Y-m-d');
            } catch (Throwable) {
                return (string) $cell;
            }
        }

        $cell = trim((string) $cell);

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $cell, $parts)) {
            return sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1]);
        }

        return $cell;
    }

    /**
     * @param  Collection<int, array{line: int, data: array<string, ?string>}>  $students
     *
     * @throws ValidationException
     */
    private function validateRows(Collection $students): void
    {
        $errors = [];
        $seenCodes = [];
        $seenPortalEmails = [];

        foreach ($students as $row) {
            ['line' => $line, 'data' => $data] = $row;

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:20', Rule::unique('students', 'code')],
                'name' => ['required', 'string', 'max:255'],
                'document' => ['nullable', 'string', 'max:50'],
                'email' => [
                    'nullable',
                    'required_with:password',
                    'email',
                    'max:255',
                    Rule::when(filled($data['password']), [Rule::unique('users', 'email')]),
                ],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(['activo', 'inactivo'])],
                'password' => ['nullable', Password::defaults()],
                ...$this->guardianRules(),
            ], $this->guardianMessages(), [
                ...$this->guardianAttributes(),
                'code' => 'código',
                'name' => 'nombre',
                'document' => 'documento',
                'email' => 'correo',
                'phone' => 'teléfono',
                'address' => 'dirección',
                'status' => 'estado',
                'password' => 'contraseña',
            ]);

            $messages = $validator->errors()->all();

            if ($data['code'] !== null && isset($seenCodes[$data['code']])) {
                $messages[] = "El código {$data['code']} está repetido (fila {$seenCodes[$data['code']]}).";
            }

            if ($data['password'] !== null && $data['email'] !== null && isset($seenPortalEmails[$data['email']])) {
                $messages[] = "El correo {$data['email']} ya se usa para otro acceso al portal (fila {$seenPortalEmails[$data['email']]}).";
            }

            if ($data['code'] !== null) {
                $seenCodes[$data['code']] ??= $line;
            }

            if ($data['password'] !== null && $data['email'] !== null) {
                $seenPortalEmails[$data['email']] ??= $line;
            }

            if ($messages !== []) {
                $errors["row_{$line}"] = "Fila {$line}: ".implode(' ', $messages);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, ?string>  $data
     */
    private function createStudent(array $data): void
    {
        $student = Student::create(collect($data)->except('password')->all());
        $this->createdCount++;

        if ($data['password'] === null) {
            return;
        }

        $user = User::create([
            'name' => $student->name,
            'email' => $student->email,
            'password' => $data['password'],
        ]);
        $user->assignRole('estudiante');
        $student->update(['user_id' => $user->id]);
        $this->portalAccessCount++;
    }
}
