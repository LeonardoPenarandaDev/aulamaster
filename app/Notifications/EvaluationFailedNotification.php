<?php

namespace App\Notifications;

use App\Models\EvaluationResult;

class EvaluationFailedNotification extends BaseNotification
{
    public function __construct(protected EvaluationResult $result)
    {
        $this->result->loadMissing('evaluation');
    }

    public function title(): string
    {
        return 'Evaluación perdida';
    }

    public function lines(): array
    {
        return [
            "Reprobaste {$this->result->evaluation->name} con nota {$this->result->grade}.",
            'Tu matrícula entró en proceso de recuperación.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
