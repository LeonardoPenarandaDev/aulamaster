<?php

namespace App\Notifications;

use App\Models\Enrollment;

class NextLevelEnrollmentNotification extends BaseNotification
{
    public function __construct(protected Enrollment $enrollment)
    {
        $this->enrollment->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Ya tienes tu siguiente nivel';
    }

    public function lines(): array
    {
        return [
            "Te matriculamos en {$this->enrollment->level->course->name} {$this->enrollment->level->name}.",
            'La matrícula queda pendiente hasta completar los requisitos con la institución.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
