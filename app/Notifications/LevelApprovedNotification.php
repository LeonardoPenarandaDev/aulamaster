<?php

namespace App\Notifications;

use App\Models\Enrollment;

class LevelApprovedNotification extends BaseNotification
{
    public function __construct(protected Enrollment $enrollment)
    {
        $this->enrollment->loadMissing('level.course');
    }

    public function title(): string
    {
        return '¡Nivel aprobado!';
    }

    public function lines(): array
    {
        return [
            "Aprobaste {$this->enrollment->level->course->name} {$this->enrollment->level->name}. ¡Felicitaciones!",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
