<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * Aviso al cajero, la secretaria y el admin el día en que un pago se vence:
 * hay que contactar al estudiante o a su acudiente (parte 8 del plan de
 * mejoras).
 */
class StudentOverdueStaffNotification extends BaseNotification
{
    public function __construct(protected Payment $payment)
    {
        $this->payment->loadMissing('student');
    }

    public function title(): string
    {
        return "Pago vencido: {$this->payment->student->name}";
    }

    public function lines(): array
    {
        $contact = $this->payment->student->isMinor() ? 'a su acudiente' : 'al estudiante';

        return [
            "{$this->payment->student->name} no pagó su {$this->payment->concept} ($".number_format((float) $this->payment->final_amount, 0, ',', '.').'). Su acceso quedó bloqueado.',
            "Contacta {$contact} desde la cartera en mora.",
        ];
    }

    public function actionText(): ?string
    {
        return 'Ver cartera en mora';
    }

    public function actionUrl(): ?string
    {
        return route('collections.index');
    }
}
