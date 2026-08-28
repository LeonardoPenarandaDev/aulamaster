<?php

namespace App\Notifications;

use App\Models\ClassSession;

class ClassCancelledNotification extends BaseNotification
{
    public function __construct(protected ClassSession $classSession)
    {
        $this->classSession->loadMissing(['level.course']);
    }

    public function title(): string
    {
        return 'Clase cancelada';
    }

    public function lines(): array
    {
        $session = $this->classSession;

        return [
            "Se canceló la clase de {$session->level->course->name} {$session->level->name} del {$session->date->format('d/m/Y')} ".
                '('.substr($session->start_time, 0, 5).' a '.substr($session->end_time, 0, 5).').',
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
