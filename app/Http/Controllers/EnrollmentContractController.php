<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Actions\Contracts\GenerateEnrollmentContracts;
use App\Actions\Contracts\RenderContractPdf;
use App\Actions\Contracts\ResolveContractSigner;
use App\Actions\Contracts\SendContractsForSigning;
use App\Actions\Contracts\SignContract;
use App\Actions\Contracts\VoidContract;
use App\Http\Requests\SignContractInOfficeRequest;
use App\Models\ContractSignature;
use App\Models\Enrollment;
use App\Services\ContractRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Contratos de una matrícula desde la oficina (partes 6.5 a 6.8 del plan de
 * mejoras): generar, firmar aquí, enviar para firma, anular y descargar.
 */
class EnrollmentContractController extends Controller
{
    /**
     * Generate the pending contracts of the enrollment.
     */
    public function store(Request $request, Enrollment $enrollment, GenerateEnrollmentContracts $generate): RedirectResponse
    {
        Gate::authorize('manage', ContractSignature::class);

        $validated = $request->validate([
            'special_clauses' => ['array'],
            'special_clauses.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $contracts = $generate->handle($enrollment, $request->user(), $validated['special_clauses'] ?? []);

        return back()->with('success', "Se generaron {$contracts->count()} contratos. Revísalos y elige cómo firmarlos.");
    }

    /**
     * Create (or renew) the remote signing link.
     */
    public function send(Request $request, Enrollment $enrollment, SendContractsForSigning $send): RedirectResponse
    {
        Gate::authorize('manage', ContractSignature::class);

        $validated = $request->validate([
            'via' => ['required', Rule::in(['correo', 'whatsapp', 'enlace'])],
        ]);

        $result = $send->handle($enrollment, $validated['via'], $request->user());

        $message = match ($validated['via']) {
            'correo' => "Enlace enviado por correo a {$result['signer']['email']}.",
            'whatsapp' => 'Enlace listo para enviar por WhatsApp.',
            default => 'Enlace generado. Cópialo y compártelo con quien firma.',
        };

        return back()
            ->with('success', $message)
            ->with('contractLink', [
                'url' => $result['url'],
                'via' => $validated['via'],
                'expires_at' => $result['expires_at'],
                'phone' => $result['signer']['phone'],
                'signer_name' => $result['signer']['name'],
            ]);
    }

    /**
     * "Firmar aquí": pantalla completa para firmar en el computador de la
     * oficina. Muestra el siguiente contrato abierto de la matrícula.
     */
    public function signInOffice(Enrollment $enrollment, ResolveContractSigner $resolveSigner, ContractRenderer $renderer): Response|RedirectResponse
    {
        Gate::authorize('manage', ContractSignature::class);

        $open = $enrollment->contractSignatures()->open()->with('template')->orderBy('id')->get();

        if ($open->isEmpty()) {
            return to_route('enrollments.edit', $enrollment)->with('success', 'No quedan contratos por firmar en esta matrícula.');
        }

        $signature = $open->first();
        $enrollment->loadMissing(['student', 'level.course']);

        return Inertia::render('Contracts/SignOffice', [
            'enrollment' => [
                'id' => $enrollment->id,
                'student' => $enrollment->student->name,
                'level' => trim(($enrollment->level?->course?->name ?? '').' '.($enrollment->level?->name ?? '')),
            ],
            'contract' => [
                'id' => $signature->id,
                'name' => $signature->template->name,
                'version' => $signature->template_version,
                'is_optional' => $signature->template->acceptance_mode === 'opcional',
                'body_html' => $renderer->toHtml($signature->rendered_body)->toHtml(),
            ],
            'progress' => [
                'signed' => $enrollment->contractSignatures()->where('status', 'firmado')->count(),
                'remaining' => $open->count(),
            ],
            'signer' => $resolveSigner->handle($enrollment->student),
            'canReusePhotos' => $this->reusablePhotoSignature($enrollment) !== null,
            'maxPhotoKb' => config('contracts.id_photo_max_kb'),
        ]);
    }

    /**
     * Save an office signature and continue with the next contract.
     */
    public function storeOfficeSignature(SignContractInOfficeRequest $request, ContractSignature $contractSignature, SignContract $sign): RedirectResponse
    {
        $enrollment = $contractSignature->enrollment;
        $reuseFrom = $request->hasFile('id_front') ? null : $this->reusablePhotoSignature($enrollment);

        if (! $request->hasFile('id_front') && ! $reuseFrom) {
            return back()->withErrors(['id_front' => 'Toma la foto del documento de identidad (anverso).']);
        }

        $sign->handle($contractSignature, [
            'method' => 'oficina',
            'signature_png' => $request->validated('signature_png'),
            'decision' => $request->validated('decision') ?? 'acepta',
            'signer_name' => $request->validated('signer_name'),
            'signer_document' => $request->validated('signer_document'),
            'signer_role' => $request->validated('signer_role'),
            'signer_email' => $request->validated('signer_email'),
            'timezone' => $request->validated('timezone'),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'identity_verified_by' => $request->user(),
            'id_front' => $request->file('id_front'),
            'id_back' => $request->file('id_back'),
            'reuse_id_photos_from' => $reuseFrom,
        ]);

        return to_route('enrollments.contracts.sign', $enrollment)->with('success', 'Contrato firmado.');
    }

    /**
     * PDF del contrato: vista previa si no está firmado, el definitivo si sí.
     */
    public function pdf(ContractSignature $contractSignature, RenderContractPdf $renderPdf): HttpResponse
    {
        Gate::authorize('view', $contractSignature);

        $fileName = Str::slug($contractSignature->template->name.' '.$contractSignature->student->name).'.pdf';

        if ($contractSignature->pdf_path && Storage::disk('local')->exists($contractSignature->pdf_path)) {
            return Storage::disk('local')->response($contractSignature->pdf_path, $fileName, ['Content-Type' => 'application/pdf']);
        }

        return $renderPdf->handle($contractSignature)->stream($fileName);
    }

    /**
     * Void a contract.
     */
    public function void(Request $request, ContractSignature $contractSignature, VoidContract $void): RedirectResponse
    {
        Gate::authorize('void', $contractSignature);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $void->handle($contractSignature, $request->user(), $validated['reason'] ?? null);

        return back()->with('success', 'Contrato anulado. Puedes generar uno nuevo.');
    }

    /**
     * Ver la foto del documento de identidad (cifrada en disco). Cada acceso
     * queda en la auditoría.
     */
    public function idPhoto(ContractSignature $contractSignature, string $side, RecordAuditEvent $recordAudit): HttpResponse
    {
        Gate::authorize('viewIdPhoto', $contractSignature);
        abort_unless(in_array($side, ['anverso', 'reverso'], true), 404);

        $path = $side === 'anverso' ? $contractSignature->id_front_path : $contractSignature->id_back_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $contents = Crypt::decryptString(Storage::disk('local')->get($path));

        $recordAudit->handle(
            'foto_documento_vista',
            'contratos',
            $contractSignature,
            "Consulta de la foto del documento ({$side}) del firmante {$contractSignature->signer_name}",
        );

        return response($contents, 200, [
            'Content-Type' => (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream',
            'Cache-Control' => 'no-store, private',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * En la oficina, la foto del documento tomada para un contrato sirve
     * para los siguientes del mismo firmante durante un tiempo corto.
     */
    private function reusablePhotoSignature(Enrollment $enrollment): ?ContractSignature
    {
        return $enrollment->contractSignatures()
            ->where('status', 'firmado')
            ->where('signing_method', 'oficina')
            ->whereNotNull('id_front_path')
            ->where('signed_at', '>=', now()->subMinutes(config('contracts.office_photo_reuse_minutes')))
            ->latest('signed_at')
            ->first();
    }
}
