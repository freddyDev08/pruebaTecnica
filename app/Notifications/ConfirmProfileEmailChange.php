<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmProfileEmailChange extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu nuevo correo electrónico')
            ->greeting('Confirmación de cambio de correo')
            ->line('Se solicitó utilizar esta dirección de correo en una cuenta del Programa de Lealtad.')
            ->line('Para confirmar que este correo es tuyo, utiliza el siguiente enlace. Caduca en 60 minutos.')
            ->action('Confirmar nuevo correo', $this->url)
            ->line('Si no solicitaste este cambio, ignora el mensaje.');
    }
}
