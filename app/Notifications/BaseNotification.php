<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base común para las notificaciones de la sección 47 del plan: cada
 * notificación concreta solo declara título, líneas de texto y (opcional)
 * un botón de acción; el envío por database/mail y el encolado son
 * idénticos para todas, así que viven una sola vez aquí. Se encolan
 * (sección 43 del plan) para no bloquear la operación que las dispara.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function title(): string;

    /**
     * @return array<int, string>
     */
    abstract public function lines(): array;

    public function actionText(): ?string
    {
        return null;
    }

    public function actionUrl(): ?string
    {
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        // Los envíos "on-demand" (estudiantes/profesores sin cuenta de portal)
        // solo pueden recibir correo: no hay a qué modelo asociar el registro
        // del canal "database".
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title())->greeting($this->title());

        foreach ($this->lines() as $line) {
            $mail->line($line);
        }

        if ($this->actionUrl()) {
            $mail->action($this->actionText() ?? 'Ver más', $this->actionUrl());
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title(),
            'lines' => $this->lines(),
            'action_text' => $this->actionText(),
            'action_url' => $this->actionUrl(),
        ];
    }
}
