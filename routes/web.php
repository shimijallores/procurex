<?php

use App\Http\Controllers\SessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Inertia\Response|\Inertia\ResponseFactory {
    return redirect('login');
});

Route::get('/login', [SessionController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [SessionController::class, 'login'])->name('login.login')->middleware('guest');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');
        
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');    
});

