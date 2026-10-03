<?php

namespace App\Actions\Contracts;

use App\Actions\Audit\RecordAuditEvent;
use App\Actions\Enrollments\ActivateEnrollmentIfReady;
use App\Models\ContractSignature;
use App\Models\User;
use App\Notifications\ContractSignedNotification;
use App\Notifications\SignedContractCopyNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SignContract
{
    public function __construct(
        protected RenderContractPdf $renderPdf,
        protected RecordAuditEvent $recordAudit,
        protected ActivateEnrollmentIfReady $activateEnrollment,
    ) {}

    /**
     * Registra la firma con su evidencia, genera el PDF definitivo y revisa
     * si la matrícula ya puede activarse (partes 6.4, 6.7 y 6.8 del plan de
     * mejoras).
     *
     * @param  array{
     *     method: 'oficina'|'distancia',
     *     signature_png: string,
     *     decision: 'acepta'|'no_acepta',
     *     signer_name: string,
     *     signer_document: string|null,
     *     signer_role: 'alumno'|'acudiente',
     *     signer_email?: string|null,
     *     timezone?: string|null,
     *     ip?: string|null,
     *     user_agent?: string|null,
     *     otp_verified_at?: \DateTimeInterface|null,
     *     identity_verified_by?: User|null,
     *     id_front?: UploadedFile|null,
     *     id_back?: UploadedFile|null,
     *     reuse_id_photos_from?: ContractSignature|null,
     * }  $data
     *
     * @throws ValidationException
     */
    public function handle(ContractSignature $signature, array $data): ContractSignature
    {
        if (! $signature->isOpen()) {
            throw ValidationException::withMessages(['signature' => 'Este contrato ya no se puede firmar (está firmado o anulado).']);
        }

        $png = $this->decodeSignature($data['signature_png']);

        DB::transaction(function () use ($signature, $data, $png) {
            $signaturePath = "contracts/signatures/{$signature->id}.png";
            Storage::disk('local')->put($signaturePath, $png);

            [$frontPath, $backPath] = $this->storeIdPhotos($signature, $data);

            $signature->update([
                'status' => 'firmado',
                'decision' => $data['decision'],
                'signing_method' => $data['method'],
                'signer_name' => $data['signer_name'],
                'signer_document' => $data['signer_document'],
                'signer_role' => $data['signer_role'],
                'signer_email' => $data['signer_email'] ?? null,
                'signature_path' => $signaturePath,
                'signed_at' => now(),
                'signer_timezone' => $this->validTimezone($data['timezone'] ?? null),
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'otp_verified_at' => $data['otp_verified_at'] ?? null,
                'identity_verified_by_id' => ($data['identity_verified_by'] ?? null)?->id,
                'id_front_path' => $frontPath,
                'id_back_path' => $backPath,
            ]);

            if ($signature->template->type === 'imagenes') {
                $signature->student->forceFill([
                    'image_consent' => $data['decision'] === 'acepta',
                    'image_consent_updated_at' => now(),
                ])->save();
            }

            $this->renderPdf->store($signature);

            $this->recordAudit->handle(
                'firmado',
                'contratos',
                $signature,
                "Contrato «{$signature->template->name}» de {$signature->student->name} firmado por {$signature->signer_name} ({$data['method']})",
                [
                    'decision' => $data['decision'],
                    'signer_role' => $data['signer_role'],
                    'content_hash' => $signature->content_hash,
                    'ip' => $data['ip'] ?? null,
                ],
            );
        });

        if ($signature->enrollment) {
            $this->activateEnrollment->handle($signature->enrollment);
        }

        if ($data['method'] === 'distancia') {
            $signature->sentBy?->notify(new ContractSignedNotification($signature));

            if ($signature->signer_email) {
                Notification::route('mail', $signature->signer_email)->notify(new SignedContractCopyNotification($signature));
            }
        }

        return $signature->fresh();
    }

    /**
     * La firma llega del lienzo como "data:image/png;base64,…".
     *
     * @throws ValidationException
     */
    protected function decodeSignature(string $dataUrl): string
    {
        $png = base64_decode((string) preg_replace('#^data:image/png;base64,#', '', $dataUrl), true);

        if ($png === false || ! str_starts_with($png, "\x89PNG") || strlen($png) > 1024 * 1024 || strlen($png) < 100) {
            throw ValidationException::withMessages(['signature_png' => 'La firma no es válida. Vuelve a firmar.']);
        }

        return $png;
    }

    /**
     * Las fotos del documento se guardan cifradas en el disco privado y
     * nunca van en el PDF. En la oficina se pueden reutilizar las del
     * contrato anterior del mismo firmante.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string|null, 1: string|null}
     */
    protected function storeIdPhotos(ContractSignature $signature, array $data): array
    {
        $paths = [null, null];

        foreach (['id_front' => 0, 'id_back' => 1] as $field => $index) {
            $file = $data[$field] ?? null;

            if ($file instanceof UploadedFile) {
                $path = "contracts/id-photos/{$signature->id}-{$field}.enc";
                Storage::disk('local')->put($path, Crypt::encryptString($file->get()));
                $paths[$index] = $path;
            }
        }

        $previous = $data['reuse_id_photos_from'] ?? null;
        if ($paths[0] === null && $previous instanceof ContractSignature) {
            $paths = [$previous->id_front_path, $previous->id_back_path];
        }

        return $paths;
    }

    protected function validTimezone(?string $timezone): string
    {
        return $timezone && in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone');
    }
}
