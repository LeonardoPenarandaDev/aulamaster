<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $signature->template->name }}</title>
    <style>
        @page { margin: 2.2cm 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.55; color: #1f2937; }
        .header { border-bottom: 1px solid #d1d5db; padding-bottom: 10px; margin-bottom: 18px; }
        .header img { height: 48px; float: right; }
        .header h1 { font-size: 15px; margin: 0; }
        .header p { margin: 2px 0 0; color: #6b7280; font-size: 10px; }
        .body p { margin: 0 0 10px; text-align: justify; }
        .signature { margin-top: 30px; page-break-inside: avoid; }
        .signature img { height: 80px; }
        .signature .line { border-top: 1px solid #374151; width: 260px; padding-top: 4px; }
        .decision { margin-top: 18px; padding: 8px 10px; border: 1px solid #d1d5db; }
        .draft { color: #b45309; border: 1px dashed #b45309; padding: 6px 10px; margin-bottom: 14px; }
        .evidence { page-break-before: always; }
        .evidence h2 { font-size: 14px; margin: 0 0 4px; }
        .evidence table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .evidence td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        .evidence td.label { width: 34%; background: #f9fafb; font-weight: bold; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all; }
        .legal { margin-top: 14px; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="">
        @endif
        <h1>{{ $signature->template->name }}</h1>
        <p>{{ $institution->name }}@if ($institution->tax_id) · NIT {{ $institution->tax_id }}@endif · Versión {{ $signature->template_version }}</p>
    </div>

    @unless ($signature->isSigned())
        <div class="draft">VISTA PREVIA — Documento sin firmar.</div>
    @endunless

    <div class="body">{!! $bodyHtml !!}</div>

    @if ($signature->isSigned())
        @if ($signature->template->acceptance_mode === 'opcional')
            <div class="decision">
                Decisión del firmante: <strong>{{ $signature->decision === 'acepta' ? 'SÍ acepta' : 'NO acepta' }}</strong>
            </div>
        @endif

        <div class="signature">
            @if ($signatureImage)
                <img src="{{ $signatureImage }}" alt="Firma">
            @endif
            <div class="line">
                <strong>{{ $signature->signer_name }}</strong><br>
                {{ $signature->signer_document }}<br>
                {{ $signature->signer_role === 'acudiente' ? 'Acudiente de '.$signature->student->name : 'Estudiante' }}
            </div>
        </div>

        <div class="evidence">
            <h2>Hoja de evidencia de firma electrónica</h2>
            <p>Este documento fue firmado electrónicamente. Los siguientes datos permiten verificar quién, cuándo y cómo lo firmó.</p>

            <table>
                <tr><td class="label">Documento</td><td>{{ $signature->template->name }} (versión {{ $signature->template_version }})</td></tr>
                <tr><td class="label">Estudiante</td><td>{{ $signature->student->name }}</td></tr>
                @if ($signature->enrollment)
                    <tr><td class="label">Matrícula</td><td>{{ $signature->enrollment->level?->course?->name }} {{ $signature->enrollment->level?->name }} (#{{ $signature->enrollment_id }})</td></tr>
                @endif
                <tr><td class="label">Firmante</td><td>{{ $signature->signer_name }} · {{ $signature->signer_document }} · {{ $signature->signer_role }}</td></tr>
                <tr><td class="label">Fecha y hora</td><td>{{ $signature->signed_at->copy()->setTimezone($signature->signer_timezone ?: config('app.timezone'))->format('d/m/Y H:i:s') }} ({{ $signature->signer_timezone ?: config('app.timezone') }}) · {{ $signature->signed_at->copy()->utc()->format('Y-m-d H:i:s') }} UTC</td></tr>
                <tr><td class="label">Modalidad</td><td>{{ $signature->signing_method === 'oficina' ? 'Presencial, en la oficina de la institución' : 'A distancia, mediante enlace único' }}</td></tr>
                @if ($signature->signing_method === 'oficina')
                    <tr><td class="label">Identidad verificada por</td><td>{{ $signature->identityVerifiedBy?->name ?? '—' }} (documento físico revisado; foto archivada aparte)</td></tr>
                @else
                    <tr><td class="label">Correo verificado</td><td>{{ $signature->signer_email }} · código confirmado el {{ $signature->otp_verified_at?->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
                @endif
                <tr><td class="label">Dirección IP</td><td>{{ $signature->ip ?? '—' }}</td></tr>
                <tr><td class="label">Navegador</td><td class="mono">{{ $signature->user_agent ?? '—' }}</td></tr>
                <tr><td class="label">Huella del texto (SHA-256)</td><td class="mono">{{ $signature->content_hash }}</td></tr>
            </table>

            <p class="legal">
                Firma electrónica conforme a la Ley 527 de 1999 y el Decreto 2364 de 2012. Datos personales tratados según la Ley 1581 de 2012.
                La huella SHA-256 corresponde al texto exacto del contrato mostrado al firmante; cualquier cambio en el texto produce una huella distinta.
            </p>
        </div>
    @endif
</body>
</html>
