<?php

namespace App\Notifications;

use App\Models\Enrollment;

class EnrollmentActivatedNotification extends BaseNotification
{
    public function __construct(protected Enrollment $enrollment)
    {
        $this->enrollment->loadMissing('level.course');
    }

    public function title(): string
    {
        return '¡Tu matrícula está activa!';
    }

    public function lines(): array
    {
        return [
            "Tu matrícula en {$this->enrollment->level->course->name} {$this->enrollment->level->name} ya está activa. ¡Te esperamos en clase!",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
