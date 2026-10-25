<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmación de cambio de contraseña')
            ->greeting('Hola, '.$notifiable->name)
            ->line('La contraseña de tu cuenta del Programa de Lealtad se cambió correctamente.')
            ->line('Si no realizaste este cambio, solicita la recuperación de contraseña de inmediato.')
            ->action('Recuperar contraseña', route('password.request'));
    }
}
