<?php

use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ProfileEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/profile/email/confirm/{token}', [ProfileEmailController::class, 'confirm'])
    ->middleware('throttle:6,1')->name('profile.email.confirm');

Route::get('/health', fn () => response()->json(['status' => 'ok', 'framework' => 'Laravel 11']));
Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/password-email-sent', fn () => view('auth.confirm-mail', ['reason' => 'reset']))->name('password.email.sent');
});

Route::middleware(['auth', 'account.approved'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.send');
    Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/mi-perfil/email', [ProfileEmailController::class, 'requestChange'])
        ->middleware('throttle:3,1')->name('profile.email.request');
    Route::delete('/mi-perfil/email', [ProfileEmailController::class, 'cancel'])->name('profile.email.cancel');
    Route::get('/account/password', [ChangePasswordController::class, 'create'])->name('password.change');
    Route::put('/account/password', [ChangePasswordController::class, 'update'])->name('password.change.update');
});

Route::middleware(['auth', 'account.approved', 'verified', 'staff.active', 'staff.password'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        abort_unless($request->user()->allows('panel.view'), 403);
        return view('layouts.body');
    })->name('dashboard');

    Route::get('/admin/clientes', [ClienteController::class, 'index'])->name('admin.clientes.index');
    Route::get('/admin/clientes/datos', [ClienteController::class, 'data'])->name('admin.clientes.data');
    Route::post('/admin/clientes/sincronizar', [ClienteController::class, 'sync'])->name('admin.clientes.sync');

    Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/usuarios/crear', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/usuarios', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/usuarios/{user}/editar', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/usuarios/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::patch('/admin/usuarios/{user}/rol', [UserController::class, 'assignRole'])->name('admin.users.role');
    Route::patch('/admin/usuarios/{user}/estado', [UserController::class, 'toggleActive'])->name('admin.users.toggle');
    Route::delete('/admin/usuarios/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/roles', [RoleController::class, 'index'])->name('admin.roles.index');
    Route::post('/admin/roles', [RoleController::class, 'store'])->name('admin.roles.store');
    Route::put('/admin/roles/{role}/permisos', [RoleController::class, 'updatePermissions'])->name('admin.roles.permissions.update');
    Route::delete('/admin/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');

});
