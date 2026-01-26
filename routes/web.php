<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Inertia\Response|\Inertia\ResponseFactory {
    return redirect('login.index');
});

Route::get('/login', [SessionController::class, 'index'])->name('login')->middleware('guest');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');    
});

