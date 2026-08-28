<?php

namespace App\Actions\Notifications;

use App\Models\Teacher;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifyTeacher
{
    public function handle(Teacher $teacher, Notification $notification): void
    {
        if ($teacher->user) {
            $teacher->user->notify($notification);

            return;
        }

        if ($teacher->email) {
            NotificationFacade::route('mail', $teacher->email)->notify($notification);
        }
    }
}
