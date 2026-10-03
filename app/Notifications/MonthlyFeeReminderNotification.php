<?php

namespace App\Notifications;

use App\Models\Payment;

class MonthlyFeeReminderNotification extends BaseNotification
{
    public function __construct(protected Payment $payment) {}

    public function title(): string
    {
        return 'Recordatorio: tu mensualidad vence el '.$this->payment->due_date->format('d/m');
    }

    public function lines(): array
    {
        return [
            "Tu {$this->payment->concept} por $".number_format((float) $this->payment->final_amount, 0, ',', '.').' vence el '.$this->payment->due_date->format('d/m/Y').'.',
            'Si ya pagaste, ignora este mensaje.',
        ];
    }

    public function actionText(): ?string
    {
        return 'Pagar en línea';
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
