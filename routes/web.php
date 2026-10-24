<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('layouts.body');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'framework' => 'Laravel 11'
    ]);
});