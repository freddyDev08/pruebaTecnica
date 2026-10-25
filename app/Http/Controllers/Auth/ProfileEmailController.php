<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Notifications\ConfirmProfileEmailChange;
use App\Notifications\EmailChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileEmailController extends Controller
{
    public function requestChange(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = $request->user();
        if ($data['email'] === Str::lower($user->email)) {
            throw ValidationException::withMessages(['email' => 'El correo es igual al que ya tienes.']);
        }

        $token = Str::random(64);
        $hash = hash('sha256', $token);
        $pending = EmailChangeRequest::updateOrCreate(
            ['user_id' => $user->id],
            ['new_email' => $data['email'], 'token_hash' => $hash, 'expires_at' => now()->addHour()]
        );

        $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['token' => $token]);
        try {
            Notification::route('mail', $data['email'])->notify(new ConfirmProfileEmailChange($url));
        } catch (Throwable $e) {
            report($e);
            EmailChangeRequest::whereKey($pending->id)->where('token_hash', $hash)->delete();
            return back()->with('warning', 'No se pudo enviar el correo de confirmación. Revisa el servicio de correo e intenta de nuevo.');
        }

        return redirect()->route('profile.show')->with('status',
            'Enviamos un enlace de confirmación al nuevo correo. Hasta confirmarlo, tu correo actual seguirá funcionando.');
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('login')->with('warning',
                'El enlace de confirmación es inválido o ha vencido. Solicita uno nuevo desde Mi perfil.');
        }

        $hash = hash('sha256', $token);
        $oldEmail = null;
        $newEmail = null;
        $changedUserId = null;

        try {
            DB::transaction(function () use ($hash, &$oldEmail, &$newEmail, &$changedUserId) {
                $pending = EmailChangeRequest::where('token_hash', $hash)->lockForUpdate()->first();
                if (! $pending || $pending->expires_at->isPast()) {
                    throw ValidationException::withMessages(['email' => 'El enlace venció o ya fue utilizado. Solicita otro desde Mi perfil.']);
                }
                $user = User::whereKey($pending->user_id)->lockForUpdate()->firstOrFail();
                if (! $user->isApproved()) {
                    throw ValidationException::withMessages(['email' => 'Esta cuenta no está habilitada.']);
                }
                if (User::where('email', $pending->new_email)->where('id', '!=', $user->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'El nuevo correo ya pertenece a otra cuenta.']);
                }
                $oldEmail = $user->email;
                $newEmail = $pending->new_email;
                $changedUserId = $user->id;
                $user->forceFill(['email' => $newEmail, 'email_verified_at' => now()])->save();
                $pending->delete();
            });
        } catch (ValidationException $e) {
            return redirect()->route('login')->with('warning', $e->errors()['email'][0]);
        } catch (UniqueConstraintViolationException $e) {
            return redirect()->route('login')->with('warning',
                'Ese correo ya está registrado en otra cuenta. Solicita un correo diferente.');
        }

        // Se notifica la dirección anterior como medida de seguridad (sin bloquear el cambio si falla SMTP).
        try {
            Notification::route('mail', $oldEmail)->notify(new EmailChangedNotification($newEmail));
        } catch (Throwable $e) {
            report($e);
        }

        $destination = auth()->id() === $changedUserId ? 'profile.show' : 'login';

        return redirect()->route($destination)->with('status',
            'Tu nuevo correo fue confirmado. Ya puedes iniciar sesión con él.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        EmailChangeRequest::where('user_id', $request->user()->id)->delete();
        return redirect()->route('profile.show')->with('status', 'Se canceló el cambio de correo pendiente.');
    }
}
