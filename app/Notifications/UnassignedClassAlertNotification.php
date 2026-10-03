<?php

namespace App\Notifications;

use App\Models\ClassSession;

/**
 * Alerta 48 horas antes de una clase que todavía no tiene docente (parte 11
 * del plan de mejoras).
 */
class UnassignedClassAlertNotification extends BaseNotification
{
    public function __construct(protected ClassSession $classSession)
    {
        $this->classSession->loadMissing('level.course');
    }

    public function title(): string
    {
        return 'Clase sin docente';
    }

    public function lines(): array
    {
        $session = $this->classSession;

        return [
            "La clase de {$session->level->course->name} {$session->level->name} del {$session->date->format('d/m/Y')} a las ".substr($session->start_time, 0, 5).' todavía no tiene docente.',
        ];
    }

    public function actionText(): ?string
    {
        return 'Asignar docente';
    }

    public function actionUrl(): ?string
    {
        return route('class-sessions.edit', ['class_session' => $this->classSession->id, 'return_to' => 'calendar']);
    }
}
