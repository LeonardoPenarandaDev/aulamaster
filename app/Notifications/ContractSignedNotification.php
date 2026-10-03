<?php

namespace App\Notifications;

use App\Models\ContractSignature;

/**
 * Aviso a quien envió el enlace de firma a distancia (parte 6.8 del plan de
 * mejoras).
 */
class ContractSignedNotification extends BaseNotification
{
    public function __construct(protected ContractSignature $signature)
    {
        $this->signature->loadMissing(['template:id,name', 'student:id,name']);
    }

    public function title(): string
    {
        return 'Contrato firmado';
    }

    public function lines(): array
    {
        return [
            "{$this->signature->signer_name} firmó «{$this->signature->template->name}» de {$this->signature->student->name}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return $this->signature->enrollment_id ? route('enrollments.edit', $this->signature->enrollment_id) : null;
    }
}
