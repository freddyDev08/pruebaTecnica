<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Confirma tu correo - Programa de Lealtad')
                ->greeting('Hola, '.$notifiable->name)
                ->line('Confirma tu dirección de correo para acceder al panel administrativo.')
                ->action('Verificar correo', $url)
                ->line('Si no solicitaste esta cuenta, puedes ignorar este mensaje.');
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Recuperar contraseña - Programa de Lealtad')
                ->greeting('Hola, '.$notifiable->name)
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                ->action('Restablecer contraseña', $url)
                ->line('Este enlace vence en '.config('auth.passwords.users.expire', 60).' minutos.')
                ->line('Si no realizaste esta solicitud, puedes ignorar este mensaje.');
        });
    }
}
