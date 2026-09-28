<?php

namespace App\Notifications;

use App\Models\ClassSession;

class UpcomingClassNotification extends BaseNotification
{
    /**
     * El nombre del profesor solo se incluye en el aviso al propio profesor:
     * a los estudiantes no se les informa quién dicta la clase.
     */
    public function __construct(protected ClassSession $classSession, protected bool $includeTeacher = false)
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
            $this->includeTeacher
                ? "Aula: {$session->classroom->name}. Profesor: {$session->teacher->name}."
                : "Aula: {$session->classroom->name}.",
        ];
    }

    public function actionUrl(): ?string
    {
        return route('dashboard');
    }
}
