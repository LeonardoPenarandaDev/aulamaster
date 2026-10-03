<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Carbon\CarbonInterface;

/**
 * Correo con el enlace único para firmar los contratos a distancia (parte
 * 6.8 del plan de mejoras).
 */
class ContractSigningLinkNotification extends BaseNotification
{
    public function __construct(
        protected Enrollment $enrollment,
        protected string $signerName,
        protected string $url,
        protected CarbonInterface $expiresAt,
        protected string $institutionName,
    ) {
        $this->enrollment->loadMissing('student:id,name');
    }

    public function title(): string
    {
        return "Contratos para firmar - {$this->institutionName}";
    }

    public function lines(): array
    {
        return [
            "Hola {$this->signerName}, {$this->institutionName} te envía los contratos de matrícula de {$this->enrollment->student->name} para que los leas y los firmes desde tu celular o computador.",
            'Al firmar te enviaremos un código de verificación a este correo.',
            'El enlace vence el '.$this->expiresAt->format('d/m/Y').'.',
        ];
    }

    public function actionText(): ?string
    {
        return 'Leer y firmar';
    }

    public function actionUrl(): ?string
    {
        return $this->url;
    }
}
