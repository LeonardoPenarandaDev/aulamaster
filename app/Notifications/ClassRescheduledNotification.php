<?php

namespace App\Notifications;

use App\Models\ClassSession;

/**
 * Cambio de fecha u hora de una clase hecho desde el calendario (parte 10
 * del plan de mejoras).
 */
class ClassRescheduledNotification extends BaseNotification
{
    public function __construct(protected ClassSession $classSession, protected string $previousSchedule)
    {
        $this->classSession->loadMissing(['level.course']);
    }

    public function title(): string
    {
        return 'Clase reprogramada';
    }

    public function lines(): array
    {
        $session = $this->classSession;

        return [
            "La clase de {$session->level->course->name} {$session->level->name} cambió de horario.",
            "Antes: {$this->previousSchedule}. Ahora: {$session->date->format('d/m/Y')} de ".substr($session->start_time, 0, 5).' a '.substr($session->end_time, 0, 5).'.',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('calendar.index');
    }
}
