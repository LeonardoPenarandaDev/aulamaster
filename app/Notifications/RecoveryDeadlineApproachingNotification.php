<?php

namespace App\Notifications;

use App\Models\Evaluation;
use Illuminate\Support\Carbon;

class RecoveryDeadlineApproachingNotification extends BaseNotification
{
    public function __construct(protected Evaluation $evaluation, protected Carbon $deadline, protected int $daysRemaining)
    {
        $this->evaluation->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Se acerca la fecha límite de tu recuperación';
    }

    public function lines(): array
    {
        return [
            "Quedan {$this->daysRemaining} día(s) para presentar la recuperación de {$this->evaluation->name} ".
                "({$this->evaluation->level->course->name} {$this->evaluation->level->name}).",
            "Fecha límite: {$this->deadline->format('d/m/Y')}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
