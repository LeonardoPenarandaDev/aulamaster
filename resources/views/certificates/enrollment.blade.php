<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado</title>
    <style>
        body { font-family: sans-serif; color: #1f2937; }
        .frame { border: 3px solid #4f46e5; padding: 40px; }
        .header { text-align: center; margin-bottom: 24px; }
        .header img { max-height: 70px; margin-bottom: 8px; }
        .header h2 { margin: 0; font-size: 15px; color: #4f46e5; }
        .header p { margin: 2px 0; font-size: 10px; color: #6b7280; }
        .title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 2px; margin: 36px 0 24px; color: #1f2937; }
        .body-text { text-align: center; font-size: 13px; line-height: 1.9; color: #374151; margin: 0 30px; }
        .student-name { font-size: 20px; font-weight: bold; color: #1f2937; }
        .level-name { font-weight: bold; }
        .details { margin: 32px auto 0; width: 60%; font-size: 11px; color: #6b7280; }
        .details td { padding: 3px 0; }
        .signature { margin-top: 60px; text-align: center; }
        .signature .line { width: 240px; border-top: 1px solid #9ca3af; margin: 0 auto 6px; }
        .signature .name { font-weight: bold; font-size: 12px; }
        .signature .title { font-size: 10px; color: #6b7280; }
        .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="header">
            @if ($logoPath)
                <img src="{{ $logoPath }}" alt="Logo">
            @endif
            <h2>{{ $institution->name }}</h2>
            <p>
                {{ collect([$institution->tax_id, $institution->address, $institution->phone, $institution->email])->filter()->implode(' · ') }}
            </p>
        </div>

        <div class="title">CERTIFICADO DE APROBACIÓN</div>

        <p class="body-text">
            {{ $institution->name }} certifica que
            <br><span class="student-name">{{ $enrollment->student->name }}</span><br>
            @if ($enrollment->student->document)
                identificado(a) con documento {{ $enrollment->student->document }},
            @endif
            aprobó satisfactoriamente el nivel <span class="level-name">{{ $enrollment->level->name }}</span>
            del curso <span class="level-name">{{ $enrollment->level->course->name }}</span>,
            con una intensidad de {{ $enrollment->required_hours }} horas académicas.
        </p>

        <table class="details">
            <tr><td>Fecha de finalización:</td><td>{{ optional($enrollment->actual_end_date)->format('d/m/Y') }}</td></tr>
            <tr><td>Horas cursadas:</td><td>{{ $enrollment->accumulated_hours }} / {{ $enrollment->required_hours }}</td></tr>
            <tr><td>Fecha de emisión:</td><td>{{ now()->format('d/m/Y') }}</td></tr>
        </table>

        @if ($institution->signer_name)
            <div class="signature">
                <div class="line"></div>
                <div class="name">{{ $institution->signer_name }}</div>
                <div class="title">{{ $institution->signer_title }}</div>
            </div>
        @endif

        <div class="footer">Documento generado por {{ $institution->name }}.</div>
    </div>
</body>
</html>
