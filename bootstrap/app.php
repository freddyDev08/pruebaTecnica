<?php

use App\Http\Middleware\EnsureActiveStaff;
use App\Http\Middleware\EnsureStaffPasswordChanged;
use App\Http\Middleware\EnsureApprovedAccount;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        
        $middleware->alias([
            'staff.active' => EnsureActiveStaff::class,
            'staff.password' => EnsureStaffPasswordChanged::class,
            'account.approved' => EnsureApprovedAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Manejo de excepciones de Laravel.
    })
    ->create();
