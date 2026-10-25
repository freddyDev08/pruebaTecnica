<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaff() || ! $request->user()->isApproved()) {
            $inactive = $request->user() && ! $request->user()->active;
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('warning',
                $inactive
                    ? 'Tu cuenta está deshabilitada. Contacta al administrador para solicitar su habilitación.'
                    : 'Esta cuenta no tiene acceso al panel. Contacta al administrador.');
        }

        return $next($request);
    }
}
