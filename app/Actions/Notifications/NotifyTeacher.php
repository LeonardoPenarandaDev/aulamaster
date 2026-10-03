<?php

namespace App\Actions\Notifications;

use App\Models\Teacher;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifyTeacher
{
    /**
     * Las clases sin docente (parte 11 del plan de mejoras) no notifican a nadie.
     */
    public function handle(?Teacher $teacher, Notification $notification): void
    {
        if (! $teacher) {
            return;
        }

        if ($teacher->user) {
            $teacher->user->notify($notification);

            return;
        }

        if ($teacher->email) {
            NotificationFacade::route('mail', $teacher->email)->notify($notification);
        }
    }
}
