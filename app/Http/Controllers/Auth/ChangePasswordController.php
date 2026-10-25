<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ChangePasswordController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->to(route('profile.show').'#contrasena');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'different:current_password', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();
        $request->session()->regenerate();

        try {
            $request->user()->notify(new PasswordChangedNotification());
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('profile.show')->with('warning',
                'La contraseña se actualizó, pero no fue posible enviar la confirmación por correo.');
        }

        return redirect()->route('profile.show')->with('status',
            'Contraseña actualizada. Enviamos un correo de confirmación a tu dirección registrada.');
    }
}
