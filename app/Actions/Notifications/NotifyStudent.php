<?php

namespace App\Actions\Notifications;

use App\Models\Student;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifyStudent
{
    /**
     * Envía la notificación al usuario del portal del estudiante si tiene
     * cuenta; si no, la manda solo por correo (si tiene uno registrado) sin
     * quedar en su centro de notificaciones, porque no hay a qué cuenta
     * asociarla todavía.
     */
    public function handle(Student $student, Notification $notification): void
    {
        if ($student->user) {
            $student->user->notify($notification);

            return;
        }

        if ($student->email) {
            NotificationFacade::route('mail', $student->email)->notify($notification);
        }
    }

    /**
     * Igual que handle(), y si el estudiante es menor de edad también le
     * llega por correo al acudiente (parte 8 del plan de mejoras).
     */
    public function handleWithGuardian(Student $student, Notification $notification): void
    {
        $this->handle($student, $notification);

        if ($student->isMinor() && $student->guardian_email && $student->guardian_email !== $student->email) {
            NotificationFacade::route('mail', $student->guardian_email)->notify($notification);
        }
    }
}
