<?php

use App\Http\Controllers\Forms\FormAnalyticsController;
use App\Http\Controllers\Forms\FormBuilderController;
use App\Http\Controllers\Forms\FormController;
use App\Http\Controllers\Forms\FormPublicationController;
use App\Http\Controllers\Forms\FormSubmissionController;
use App\Http\Controllers\Forms\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('dashboard/forms')->name('forms.')->group(function () {
    Route::get('/', [FormController::class, 'index'])->name('index');
    Route::get('/create', [FormController::class, 'create'])->name('create');
    Route::post('/', [FormController::class, 'store'])->name('store');
    Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
    Route::get('/{form}/settings', [FormController::class, 'edit'])->name('settings');
    Route::put('/{form}', [FormBuilderController::class, 'update'])->name('update');
    Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');
    Route::post('/{form}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');
    Route::post('/{form}/publish', [FormPublicationController::class, 'publish'])->name('publish');
    Route::post('/{form}/unpublish', [FormPublicationController::class, 'unpublish'])->name('unpublish');
    Route::post('/{form}/archive', [FormPublicationController::class, 'archive'])->name('archive');
    Route::get('/{form}/preview', [FormBuilderController::class, 'preview'])->name('preview');
    Route::get('/{form}/analytics', FormAnalyticsController::class)->name('analytics');
    Route::get('/{form}/submissions', [FormSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/{form}/export', [FormSubmissionController::class, 'export'])->name('export');
    Route::get('/{form}/submissions/{submission}', [FormSubmissionController::class, 'show'])->name('submissions.show');
    Route::delete('/{form}/submissions/{submission}', [FormSubmissionController::class, 'destroy'])->name('submissions.destroy');
    Route::get('/{form}/submissions/{submission}/files/{field}', [FormSubmissionController::class, 'download'])->name('submissions.file');
});

Route::get('/forms/public/{slug}', [PublicFormController::class, 'show'])->name('public-forms.show');
Route::post('/forms/public/{slug}/submit', [PublicFormController::class, 'submit'])->middleware('throttle:form-submissions')->name('public-forms.submit');
