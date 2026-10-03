<?php

namespace App\Policies;

use App\Models\ContractSignature;
use App\Models\User;

/**
 * Generar, firmar en la oficina, enviar, reenviar, anular y descargar
 * contratos: admin y secretaria (partes 3 y 6 del plan de mejoras). El
 * estudiante solo descarga sus contratos firmados.
 */
class ContractSignaturePolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'secretaria']);
    }

    public function view(User $user, ContractSignature $contractSignature): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        return $contractSignature->isSigned() && $contractSignature->student?->user_id === $user->id;
    }

    /**
     * La foto del documento de identidad solo la ven el admin y la
     * secretaria, y cada acceso queda en la auditoría.
     */
    public function viewIdPhoto(User $user, ContractSignature $contractSignature): bool
    {
        return $this->manage($user) && $contractSignature->hasIdPhotos();
    }

    public function void(User $user, ContractSignature $contractSignature): bool
    {
        return $this->manage($user) && $contractSignature->status !== 'anulado';
    }

    public function signInOffice(User $user, ContractSignature $contractSignature): bool
    {
        return $this->manage($user) && $contractSignature->isOpen();
    }
}
