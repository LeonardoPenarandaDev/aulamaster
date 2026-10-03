<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\Student;
use Illuminate\Support\HtmlString;

/**
 * Reemplaza las variables de una plantilla de contrato ({{alumno.nombre}},
 * {{nivel}}, …) con los datos reales del estudiante, su acudiente y la
 * matrícula (parte 6.1 del plan de mejoras). El texto resultante se guarda
 * tal cual en la firma, para que lo firmado no cambie aunque cambien los
 * datos o la plantilla después.
 */
class ContractRenderer
{
    /**
     * Variables disponibles en el editor, con su descripción.
     *
     * @var array<string, string>
     */
    public const VARIABLES = [
        'alumno.nombre' => 'Nombre del estudiante',
        'alumno.tipo_documento' => 'Tipo de documento del estudiante',
        'alumno.documento' => 'Documento del estudiante',
        'alumno.fecha_nacimiento' => 'Fecha de nacimiento del estudiante',
        'alumno.correo' => 'Correo del estudiante',
        'alumno.telefono' => 'Teléfono del estudiante',
        'alumno.direccion' => 'Dirección del estudiante',
        'acudiente.nombre' => 'Nombre del acudiente',
        'acudiente.tipo_documento' => 'Tipo de documento del acudiente',
        'acudiente.documento' => 'Documento del acudiente',
        'acudiente.parentesco' => 'Parentesco del acudiente',
        'acudiente.correo' => 'Correo del acudiente',
        'acudiente.telefono' => 'Teléfono del acudiente',
        'firmante.nombre' => 'Quien firma (el acudiente si es menor de edad)',
        'firmante.documento' => 'Documento de quien firma',
        'curso' => 'Curso',
        'nivel' => 'Nivel',
        'precio_base' => 'Precio base de la matrícula',
        'precio_final' => 'Precio final de la matrícula',
        'intensidad_horaria' => 'Horas por semana',
        'fecha_inicio' => 'Fecha de inicio',
        'fecha_fin_estimada' => 'Fecha estimada de finalización',
        'institucion.nombre' => 'Nombre de la institución',
        'institucion.nit' => 'NIT de la institución',
        'institucion.direccion' => 'Dirección de la institución',
        'fecha' => 'Fecha de hoy',
        'clausulas_especiales' => 'Cláusulas especiales del estudiante',
    ];

    /**
     * @return array<string, string>
     */
    public function values(Student $student, ?Enrollment $enrollment = null, ?string $specialClauses = null): array
    {
        $enrollment?->loadMissing('level.course');
        $institution = InstitutionSetting::current();
        $isMinor = $student->isMinor();
        $date = fn ($value) => $value?->locale('es')->translatedFormat('j \d\e F \d\e Y') ?? '';
        $money = fn ($value) => $value === null ? '' : '$'.number_format((float) $value, 0, ',', '.');
        $document = fn (?string $type, ?string $number) => trim(($type ? "{$type} " : '').($number ?? ''));

        return [
            'alumno.nombre' => $student->name,
            'alumno.tipo_documento' => Student::DOCUMENT_TYPES[$student->document_type] ?? '',
            'alumno.documento' => $document($student->document_type, $student->document),
            'alumno.fecha_nacimiento' => $date($student->birth_date),
            'alumno.correo' => $student->email ?? '',
            'alumno.telefono' => $student->phone ?? '',
            'alumno.direccion' => $student->address ?? '',
            'acudiente.nombre' => $student->guardian_name ?? '',
            'acudiente.tipo_documento' => Student::DOCUMENT_TYPES[$student->guardian_document_type] ?? '',
            'acudiente.documento' => $document($student->guardian_document_type, $student->guardian_document),
            'acudiente.parentesco' => $student->guardian_relationship ?? '',
            'acudiente.correo' => $student->guardian_email ?? '',
            'acudiente.telefono' => $student->guardian_phone ?? '',
            'firmante.nombre' => ($isMinor ? $student->guardian_name : $student->name) ?? '',
            'firmante.documento' => $isMinor
                ? $document($student->guardian_document_type, $student->guardian_document)
                : $document($student->document_type, $student->document),
            'curso' => $enrollment?->level?->course?->name ?? '',
            'nivel' => $enrollment?->level?->name ?? '',
            'precio_base' => $money($enrollment?->base_price),
            'precio_final' => $money($enrollment?->final_price),
            'intensidad_horaria' => $enrollment ? rtrim(rtrim((string) $enrollment->weekly_hours, '0'), '.').' horas por semana' : '',
            'fecha_inicio' => $date($enrollment?->start_date),
            'fecha_fin_estimada' => $date($enrollment?->estimated_end_date),
            'institucion.nombre' => $institution->name,
            'institucion.nit' => $institution->tax_id ?? '',
            'institucion.direccion' => $institution->address ?? '',
            'fecha' => $date(now()),
            'clausulas_especiales' => $specialClauses ?? '',
        ];
    }

    /**
     * Replace the variables of a template body. Las cláusulas especiales se
     * agregan al final si la plantilla no tiene la variable.
     *
     * @param  array<string, string>  $values
     */
    public function render(string $body, array $values): string
    {
        $text = preg_replace_callback(
            '/\{\{\s*([a-z_.]+)\s*\}\}/',
            fn (array $match) => array_key_exists($match[1], $values) ? $values[$match[1]] : $match[0],
            $body,
        );

        $clauses = trim($values['clausulas_especiales'] ?? '');
        if ($clauses !== '' && ! preg_match('/\{\{\s*clausulas_especiales\s*\}\}/', $body)) {
            $text = rtrim($text)."\n\nCLÁUSULAS ESPECIALES\n\n{$clauses}";
        }

        return trim($text);
    }

    /**
     * Texto plano a HTML seguro: cada bloque separado por una línea en
     * blanco es un párrafo.
     */
    public function toHtml(string $text): HtmlString
    {
        $paragraphs = preg_split('/\R{2,}/', trim($text)) ?: [];

        return new HtmlString(collect($paragraphs)
            ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph))).'</p>')
            ->implode("\n"));
    }
}
