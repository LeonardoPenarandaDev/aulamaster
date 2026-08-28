<?php

namespace App\Notifications;

use App\Models\Enrollment;

class HoursBehindNotification extends BaseNotification
{
    public function __construct(protected Enrollment $enrollment)
    {
        $this->enrollment->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Faltan horas académicas';
    }

    public function lines(): array
    {
        $enrollment = $this->enrollment;

        return [
            "En {$enrollment->level->course->name} {$enrollment->level->name} llevas {$enrollment->accumulated_hours} de {$enrollment->required_hours} horas requeridas.",
            "Te faltan {$enrollment->pending_hours} horas para completar el nivel.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
