<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * Alerta al admin cuando un pago cumple los días de mora configurados
 * (10 por defecto, parte 8 del plan de mejoras).
 */
class LongOverdueAlertNotification extends BaseNotification
{
    public function __construct(
        protected Payment $payment,
        protected int $days,
    ) {
        $this->payment->loadMissing('student');
    }

    public function title(): string
    {
        return "{$this->days} días en mora: {$this->payment->student->name}";
    }

    public function lines(): array
    {
        return [
            "{$this->payment->student->name} lleva {$this->days} días sin pagar su {$this->payment->concept} ($".number_format((float) $this->payment->final_amount, 0, ',', '.').').',
        ];
    }

    public function actionText(): ?string
    {
        return 'Ver cartera en mora';
    }

    public function actionUrl(): ?string
    {
        return route('collections.index', ['days' => 'rojo']);
    }
}
