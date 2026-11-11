<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientResponseController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Forms\FormOptionSourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->prefix('dashboard/responses')->name('clients.responses.')->group(function () {
    Route::get('/{submission}', [ClientResponseController::class, 'show'])->name('show');
    Route::get('/{submission}/files/{field}', [ClientResponseController::class, 'download'])->name('file');
});

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

Route::post('/dashboard/form-option-sources', FormOptionSourceController::class)
    ->middleware(['auth', 'can:manage-clients'])
    ->name('forms.option-sources');
