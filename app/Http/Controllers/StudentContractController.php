<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordAuditEvent;
use App\Models\ContractSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mis contratos" del estudiante: los ve solo para leer y descargar. Si es
 * menor, aparecen como firmados por su acudiente. Puede revocar la
 * autorización de uso de imágenes (partes 6.2 y 6.4 del plan de mejoras).
 */
class StudentContractController extends Controller
{
    /**
     * Display the student's signed contracts.
     */
    public function index(Request $request): Response
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        return Inertia::render('StudentContracts/Index', [
            'contracts' => $student->contractSignatures()
                ->whereIn('status', ['firmado', 'enviado', 'abierto', 'pendiente'])
                ->with('template:id,name,type,acceptance_mode')
                ->latest('id')
                ->get()
                ->map(fn (ContractSignature $signature) => [
                    'id' => $signature->id,
                    'name' => $signature->template->name,
                    'status' => $signature->status,
                    'decision' => $signature->decision,
                    'is_optional' => $signature->template->acceptance_mode === 'opcional',
                    'signer_role' => $signature->signer_role,
                    'signer_name' => $signature->signer_name,
                    'signed_at' => $signature->signed_at?->toDateString(),
                ]),
            'imageConsent' => [
                'granted' => $student->image_consent,
                'updated_at' => $student->image_consent_updated_at?->toDateString(),
            ],
        ]);
    }

    /**
     * Revoke the image consent from the portal. Queda en la auditoría.
     */
    public function revokeImageConsent(Request $request, RecordAuditEvent $recordAudit): RedirectResponse
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        if ($student->image_consent) {
            $student->forceFill(['image_consent' => false, 'image_consent_updated_at' => now()])->save();
            $recordAudit->handle('revocado', 'contratos', $student, "{$student->name} revocó la autorización de uso de imágenes");
        }

        return back()->with('success', 'Revocaste la autorización de uso de imágenes. La institución ya no podrá publicar fotos o videos tuyos.');
    }
}
