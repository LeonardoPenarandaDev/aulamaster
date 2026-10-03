<?php

namespace App\Actions\Contracts;

use App\Models\ContractSignature;
use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\User;
use App\Notifications\ContractSigningLinkNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendContractsForSigning
{
    public function __construct(
        protected ResolveContractSigner $resolveSigner,
    ) {}

    /**
     * Crea el enlace único de firma a distancia para los contratos abiertos
     * de la matrícula (parte 6.8 del plan de mejoras). Cada envío genera un
     * enlace nuevo: los enlaces anteriores dejan de funcionar.
     *
     * @param  'correo'|'whatsapp'|'enlace'  $via
     * @return array{url: string, expires_at: string, signer: array<string, string|null>}
     *
     * @throws ValidationException
     */
    public function handle(Enrollment $enrollment, string $via, User $sentBy): array
    {
        $enrollment->loadMissing('student');
        $signatures = $enrollment->contractSignatures()->open()->get();

        if ($signatures->isEmpty()) {
            throw ValidationException::withMessages(['contracts' => 'No hay contratos pendientes de firma en esta matrícula.']);
        }

        $signer = $this->resolveSigner->handle($enrollment->student);

        if (! $signer['email']) {
            throw ValidationException::withMessages([
                'contracts' => $signer['role'] === 'acudiente'
                    ? 'El acudiente necesita un correo: allí le llega el código de verificación.'
                    : 'El estudiante necesita un correo: allí le llega el código de verificación.',
            ]);
        }

        $token = Str::random(48);
        $expiresAt = now()->addDays(config('contracts.link_days'));

        $signatures->each(fn (ContractSignature $signature) => $signature->update([
            'status' => $signature->status === 'abierto' ? 'abierto' : 'enviado',
            'link_token' => $token,
            'link_expires_at' => $expiresAt,
            'sent_via' => $via,
            'sent_at' => now(),
            'sent_by_id' => $sentBy->id,
        ]));

        $url = URL::temporarySignedRoute('contracts.remote.show', $expiresAt, [
            'enrollment' => $enrollment->id,
            'token' => $token,
        ]);

        if ($via === 'correo') {
            Notification::route('mail', $signer['email'])->notify(new ContractSigningLinkNotification(
                $enrollment,
                $signer['name'] ?? '',
                $url,
                $expiresAt,
                InstitutionSetting::current()->name,
            ));
        }

        return [
            'url' => $url,
            'expires_at' => $expiresAt->toDateString(),
            'signer' => $signer,
        ];
    }
}
