<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentPendingNotification extends BaseNotification
{
    public function __construct(protected Payment $payment) {}

    public function title(): string
    {
        return 'Pago pendiente';
    }

    public function lines(): array
    {
        return [
            "Tienes un pago pendiente por {$this->payment->concept}: $".number_format($this->payment->final_amount, 0, ',', '.').'.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
