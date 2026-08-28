<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\Evaluation;

class UpcomingEvaluationNotification extends BaseNotification
{
    public function __construct(protected Enrollment $enrollment, protected Evaluation $evaluation)
    {
        $this->enrollment->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Evaluación próxima';
    }

    public function lines(): array
    {
        return [
            "Ya cumples los requisitos para presentar {$this->evaluation->name} en {$this->enrollment->level->course->name} {$this->enrollment->level->name}.",
            'Coordina con tu profesor la fecha para presentarla.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
