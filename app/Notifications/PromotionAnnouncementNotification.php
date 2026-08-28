<?php

namespace App\Notifications;

use App\Models\Promotion;

class PromotionAnnouncementNotification extends BaseNotification
{
    public function __construct(protected Promotion $promotion) {}

    public function title(): string
    {
        return 'Nueva promoción';
    }

    public function lines(): array
    {
        $value = $this->promotion->discount_type === 'porcentaje'
            ? "{$this->promotion->value}%"
            : '$'.number_format($this->promotion->value, 0, ',', '.');

        return [
            "{$this->promotion->name}: descuento de {$value}, válida hasta {$this->promotion->end_date->format('d/m/Y')}.",
        ];
    }
}
