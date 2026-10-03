<?php

namespace App\Actions\Contracts;

use App\Models\ContractSignature;
use App\Models\InstitutionSetting;
use App\Services\ContractRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\Storage;

class RenderContractPdf
{
    public function __construct(
        protected ContractRenderer $renderer,
    ) {}

    /**
     * PDF del contrato: el texto exacto que se firmó y, si ya está firmado,
     * la firma y una hoja de evidencia (parte 6.4 del plan de mejoras). La
     * foto del documento de identidad nunca va en el PDF.
     */
    public function handle(ContractSignature $signature): DomPdf
    {
        $signature->loadMissing(['template', 'student', 'enrollment.level.course', 'identityVerifiedBy:id,name']);
        $institution = InstitutionSetting::current();
        $disk = Storage::disk('local');

        return Pdf::loadView('contracts.pdf', [
            'signature' => $signature,
            'institution' => $institution,
            'bodyHtml' => $this->renderer->toHtml($signature->rendered_body),
            'logoPath' => $institution->logo_path ? Storage::disk('public')->path($institution->logo_path) : null,
            'signatureImage' => $signature->signature_path && $disk->exists($signature->signature_path)
                ? 'data:image/png;base64,'.base64_encode($disk->get($signature->signature_path))
                : null,
        ]);
    }

    /**
     * Genera el PDF definitivo y lo guarda en el disco privado.
     */
    public function store(ContractSignature $signature): string
    {
        $path = "contracts/pdf/{$signature->id}-".hash('crc32b', $signature->content_hash).'.pdf';

        Storage::disk('local')->put($path, $this->handle($signature)->output());
        $signature->forceFill(['pdf_path' => $path])->save();

        return $path;
    }
}
