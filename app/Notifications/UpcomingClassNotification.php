<?php

namespace App\Notifications;

use App\Models\ClassSession;

class UpcomingClassNotification extends BaseNotification
{
    public function __construct(protected ClassSession $classSession)
    {
        $this->classSession->loadMissing(['level.course', 'classroom', 'teacher']);
    }

    public function title(): string
    {
        return 'Próxima clase';
    }

    public function lines(): array
    {
        $session = $this->classSession;

        return [
            "{$session->level->course->name} {$session->level->name} — {$session->date->format('d/m/Y')}, ".
                substr($session->start_time, 0, 5).' a '.substr($session->end_time, 0, 5),
            "Aula: {$session->classroom->name}. Profesor: {$session->teacher->name}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
