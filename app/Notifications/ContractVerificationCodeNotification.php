<?php

namespace App\Notifications;

/**
 * Código de verificación de la firma a distancia (parte 6.8 del plan de
 * mejoras).
 */
class ContractVerificationCodeNotification extends BaseNotification
{
    public function __construct(
        protected string $code,
        protected int $minutes,
    ) {}

    public function title(): string
    {
        return "Tu código de verificación: {$this->code}";
    }

    public function lines(): array
    {
        return [
            "Escribe este código para confirmar tu firma: {$this->code}",
            "Vence en {$this->minutes} minutos. Si no pediste este código, ignora este correo.",
        ];
    }
}
