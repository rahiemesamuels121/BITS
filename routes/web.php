<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/forms.php';

Route::middleware(['auth', 'can:manage-contracts'])->prefix('dashboard')->group(function () {
    Route::resource('contracts', ContractController::class)->except('show');
});

Route::middleware(['auth', 'can:manage-clients'])->prefix('dashboard/clients')->name('clients.')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('index');
    Route::get('/create', [ClientController::class, 'create'])->name('create');
    Route::post('/', [ClientController::class, 'store'])->name('store');
    Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::put('/{client}', [ClientController::class, 'update'])->name('update');
    Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
    Route::patch('/{client}/status', [ClientController::class, 'updateStatus'])->name('status');
});
