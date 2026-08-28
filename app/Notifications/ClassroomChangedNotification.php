<?php

namespace App\Notifications;

use App\Models\ClassSession;

class ClassroomChangedNotification extends BaseNotification
{
    public function __construct(protected ClassSession $classSession, protected string $previousClassroomName)
    {
        $this->classSession->loadMissing(['level.course', 'classroom']);
    }

    public function title(): string
    {
        return 'Cambio de aula';
    }

    public function lines(): array
    {
        $session = $this->classSession;

        return [
            "La clase de {$session->level->course->name} {$session->level->name} del {$session->date->format('d/m/Y')} cambió de aula.",
            "Antes: {$this->previousClassroomName}. Ahora: {$session->classroom->name}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
