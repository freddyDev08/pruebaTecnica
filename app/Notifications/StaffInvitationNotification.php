<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class StaffInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Acceso al Programa de Lealtad')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Se creó una cuenta para ti como parte del equipo interno.')
            ->line('Correo: '.$notifiable->email)
            ->line('Contraseña temporal: '.$this->temporaryPassword)
            ->line('Inicia sesión y verifica tu correo electrónico para activar el acceso.')
            ->action('Verificar correo', $verificationUrl)
            ->line('En tu primer ingreso deberás cambiar esta contraseña desde Mi perfil.')
            ->line('Si no reconoces esta cuenta, comunícate con el administrador.');
    }
}
