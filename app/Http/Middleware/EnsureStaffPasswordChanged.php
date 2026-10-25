<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isStaff() && $request->user()?->must_change_password) {
            return redirect()->route('profile.show')->with('warning',
                'Debes cambiar la contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
