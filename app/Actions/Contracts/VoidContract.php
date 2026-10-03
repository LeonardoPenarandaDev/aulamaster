<?php

namespace App\Actions\Contracts;

use App\Actions\Audit\RecordAuditEvent;
use App\Models\ContractSignature;
use App\Models\User;

class VoidContract
{
    public function __construct(
        protected RecordAuditEvent $recordAudit,
    ) {}

    /**
     * Anula un contrato. Un contrato generado o enviado no se modifica: se
     * anula y se genera uno nuevo (parte 6.6 del plan de mejoras). Anular
     * uno firmado no borra la evidencia: el PDF y la firma se conservan.
     */
    public function handle(ContractSignature $signature, User $voidedBy, ?string $reason = null): void
    {
        $signature->update([
            'status' => 'anulado',
            'voided_at' => now(),
            'voided_by_id' => $voidedBy->id,
            'void_reason' => $reason,
            'link_token' => null,
        ]);

        $this->recordAudit->handle(
            'anulado',
            'contratos',
            $signature,
            "Contrato «{$signature->template->name}» de {$signature->student->name} anulado".($reason ? ": {$reason}" : ''),
        );
    }
}
