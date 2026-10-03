<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registra en la auditoría una acción que no es un simple cambio de modelo
 * (emitir un documento, firmar, ver una foto de identidad…), con el mismo
 * formato que AuditObserver.
 *
 * @see AuditObserver
 */
class RecordAuditEvent
{
    /**
     * @param  array<string, mixed>|null  $newValues
     */
    public function handle(string $action, string $module, Model $auditable, string $description, ?array $newValues = null): AuditLog
    {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'role' => $user?->getRoleNames()->first(),
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'description' => $description,
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
        ]);
    }
}
