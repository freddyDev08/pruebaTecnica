<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('El correo de tu cuenta ha cambiado')
            ->line('Se actualizó la dirección de correo de tu cuenta a: '.$this->newEmail)
            ->line('Si no autorizaste este cambio, contacta al administrador de inmediato.');
    }
}
