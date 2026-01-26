<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Inertia\Response|\Inertia\ResponseFactory {
    return inertia('Welcome');
});

Route::get('/login', [LoginController::class, 'index'])->name('login.index')->middleware('guest');
