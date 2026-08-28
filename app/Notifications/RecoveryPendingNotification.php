<?php

namespace App\Notifications;

use App\Models\Evaluation;
use Illuminate\Support\Carbon;

class RecoveryPendingNotification extends BaseNotification
{
    public function __construct(protected Evaluation $evaluation, protected Carbon $deadline)
    {
        $this->evaluation->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Recuperación pendiente';
    }

    public function lines(): array
    {
        return [
            "Tienes una recuperación pendiente de {$this->evaluation->name} en {$this->evaluation->level->course->name} {$this->evaluation->level->name}.",
            "Fecha límite para presentarla: {$this->deadline->format('d/m/Y')}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
