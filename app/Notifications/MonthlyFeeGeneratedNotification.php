<?php

namespace App\Notifications;

use App\Models\Payment;

class MonthlyFeeGeneratedNotification extends BaseNotification
{
    public function __construct(protected Payment $payment) {}

    public function title(): string
    {
        return "Tu {$this->payment->concept}";
    }

    public function lines(): array
    {
        return [
            "Ya está disponible tu {$this->payment->concept}: $".number_format((float) $this->payment->final_amount, 0, ',', '.').'.',
            'Puedes pagarla hasta el '.$this->payment->due_date->format('d/m/Y').'. Después de esa fecha el acceso al portal queda bloqueado hasta ponerse al día.',
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
