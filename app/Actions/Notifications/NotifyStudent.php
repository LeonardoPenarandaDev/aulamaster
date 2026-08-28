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
}
