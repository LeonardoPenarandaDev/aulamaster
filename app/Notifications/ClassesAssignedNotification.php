<?php

namespace App\Notifications;

/**
 * Aviso al docente cuando se le asignan clases desde la importación de
 * horarios (parte 11 del plan de mejoras).
 */
class ClassesAssignedNotification extends BaseNotification
{
    public function __construct(
        protected int $count,
        protected string $firstDate,
    ) {}

    public function title(): string
    {
        return 'Tienes clases nuevas asignadas';
    }

    public function lines(): array
    {
        return [
            "Se te asignaron {$this->count} clase(s) a partir del ".date('d/m/Y', strtotime($this->firstDate)).'.',
            'Revísalas en tu calendario.',
        ];
    }

    public function actionText(): ?string
    {
        return 'Ver calendario';
    }

    public function actionUrl(): ?string
    {
        return route('calendar.index', ['date' => $this->firstDate]);
    }
}
