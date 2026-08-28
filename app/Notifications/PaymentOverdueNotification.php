<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentOverdueNotification extends BaseNotification
{
    public function __construct(protected Payment $payment) {}

    public function title(): string
    {
        return 'Pago vencido';
    }

    public function lines(): array
    {
        return [
            "Tu pago por {$this->payment->concept} está vencido: $".number_format($this->payment->final_amount, 0, ',', '.').'.',
            'Ponte al día lo antes posible para evitar inconvenientes con tu matrícula.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
