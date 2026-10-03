<?php

namespace App\Actions\Enrollments;

use App\Actions\Notifications\NotifyStudent;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Notifications\EnrollmentActivatedNotification;

class ActivateEnrollmentIfReady
{
    public function __construct(
        protected NotifyStudent $notifyStudent,
    ) {}

    /**
     * Una matrícula pendiente pasa sola a "activa" cuando tiene firmados
     * los contratos obligatorios y al menos un pago registrado como pagado
     * (parte 7 del plan de mejoras). Se llama al firmar un contrato y al
     * quedar pagado un pago, en cualquier orden.
     */
    public function handle(Enrollment $enrollment): bool
    {
        $enrollment->refresh();

        if ($enrollment->status !== 'pendiente') {
            return false;
        }

        if (! $this->hasPayment($enrollment) || ! $this->hasMandatoryContractsSigned($enrollment)) {
            return false;
        }

        $enrollment->update(['status' => 'activa']);
        $this->notifyStudent->handle($enrollment->student, new EnrollmentActivatedNotification($enrollment));

        return true;
    }

    /**
     * ⏳ Pendiente de confirmar (parte 7): se propuso que baste con un pago
     * pagado de la matrícula, no con la matrícula pagada por completo.
     */
    protected function hasPayment(Enrollment $enrollment): bool
    {
        return $enrollment->payments()->where('status', 'pagado')->exists();
    }

    protected function hasMandatoryContractsSigned(Enrollment $enrollment): bool
    {
        $student = $enrollment->student;

        $signed = ContractSignature::query()
            ->where('student_id', $student->id)
            ->where('status', 'firmado')
            ->with('template:id,code')
            ->get(['id', 'enrollment_id', 'contract_template_id']);

        return ContractTemplate::query()
            ->published()
            ->where('acceptance_mode', 'obligatorio')
            ->get()
            ->filter(fn (ContractTemplate $template) => $template->appliesTo($student))
            ->every(fn (ContractTemplate $template) => $signed->contains(fn (ContractSignature $signature) => $signature->template->code === $template->code
                && ($template->scope === 'alumno' || $signature->enrollment_id === $enrollment->id)
            ));
    }
}
