<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $email = Str::lower(trim($data['email']));
        $key = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta nuevamente en '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }

        $user = $request->user();
        if (! $user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('warning',
                'Este acceso es exclusivo para usuarios internos. Contacta al administrador.');
        }

        if (! $user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('warning',
                ! $user->active
                    ? 'Tu cuenta está deshabilitada. Contacta al administrador para solicitar su habilitación.'
                    : 'Tu cuenta todavía no puede acceder. Contacta al administrador.');
        }

        if ($user->isStaff() && $user->role === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('warning', 'Tu cuenta está pendiente de asignación de rol. Contacta al administrador.');
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);

        if ($user->isStaff() && $user->must_change_password && $user->hasVerifiedEmail()) {
            return redirect()->route('profile.show')->with('warning', 'Debes cambiar la contraseña temporal para continuar.');
        }

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', 'Has cerrado sesión.');
    }
}
