<?php

use App\Http\Controllers\APPController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PPMPController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Illuminate\Http\RedirectResponse {
    return redirect(route('login'));
});

// Authentication Routes
Route::get('/login', [SessionController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [SessionController::class, 'login'])->name('login.login')->middleware('guest');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::resource('offices', OfficeController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('funds', FundController::class);

    // APP Stuff
    Route::resource('apps', APPController::class);
    Route::post('apps/{app}/import', [APPController::class, 'import'])->name('apps.import');
    Route::get('apps/{app}/download', [APPController::class, 'download'])->name('apps.download');

    // PPMP Stuff
    Route::resource('ppmps', PPMPController::class);
    Route::post('ppmps/{ppmp}/import', [PPMPController::class, 'import'])->name('ppmps.import');
    Route::get('ppmps/{ppmp}/download-csv', [PPMPController::class, 'downloadCsv'])->name('ppmps.download-csv');
    Route::post('ppmps/{ppmp}/approve', [PPMPController::class, 'approve'])->name('ppmps.approve');
    Route::post('ppmps/{ppmp}/reject', [PPMPController::class, 'reject'])->name('ppmps.reject');

    // Calendar Stuff
    Route::resource('calendars', CalendarController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('calendars/check-date', [CalendarController::class, 'checkDate'])->name('calendars.check-date');
});
