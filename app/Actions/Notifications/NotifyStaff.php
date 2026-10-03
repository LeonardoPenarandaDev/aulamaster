<?php

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifyStaff
{
    /**
     * Notifica a las cuentas activas del personal con alguno de los roles.
     *
     * @param  list<string>  $roles
     */
    public function handle(array $roles, Notification $notification): void
    {
        $users = User::role($roles)->where('is_active', true)->get();

        if ($users->isNotEmpty()) {
            NotificationFacade::send($users, $notification);
        }
    }
}
