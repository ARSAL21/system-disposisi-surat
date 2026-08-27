<?php

use App\Enums\AccountType;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSubmission\LetterSubmissionController;
use App\Http\Controllers\PublicSubmission\PublicDashboardController;
use App\Http\Controllers\PublicSubmission\SubmissionDocumentController;
use App\Http\Controllers\PublicSubmission\SubmitLetterSubmissionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('public/dashboard', PublicDashboardController::class)
        ->middleware('account:'.AccountType::PublicAccount->value)
        ->name('public.dashboard');

    Route::prefix('public/submissions')
        ->name('public.submissions.')
        ->middleware('account:'.AccountType::PublicAccount->value)
        ->group(function () {
            Route::get('/create', [LetterSubmissionController::class, 'create'])
                ->middleware('throttle:public-submission-read')
                ->name('create');
            Route::get('/', [LetterSubmissionController::class, 'index'])
                ->middleware('throttle:public-submission-read')
                ->name('index');
            Route::post('/', [LetterSubmissionController::class, 'store'])
                ->middleware('throttle:public-submission-create')
                ->name('store');
            Route::get('/{submission}/edit', [LetterSubmissionController::class, 'edit'])
                ->middleware('throttle:public-submission-read')
                ->name('edit');
            Route::get('/{submission}', [LetterSubmissionController::class, 'show'])
                ->middleware('throttle:public-submission-read')
                ->name('show');
            Route::patch('/{submission}', [LetterSubmissionController::class, 'update'])
                ->middleware('throttle:public-submission-mutation')
                ->name('update');
            Route::delete('/{submission}', [LetterSubmissionController::class, 'destroy'])
                ->middleware('throttle:public-submission-mutation')
                ->name('destroy');
            Route::put('/{submission}/document', [SubmissionDocumentController::class, 'replace'])
                ->middleware('throttle:public-submission-upload')
                ->name('document.replace');
            Route::get('/{submission}/document', [SubmissionDocumentController::class, 'download'])
                ->middleware('throttle:public-submission-read')
                ->name('document.download');
            Route::post('/{submission}/submit', SubmitLetterSubmissionController::class)
                ->middleware('throttle:public-submission-submit')
                ->name('submit');
        });

    Route::prefix('internal')
        ->name('internal.')
        ->middleware([
            'account:'.AccountType::InternalAccount->value,
            'critical-mfa',
        ])
        ->group(function () {
            Route::inertia('dashboard', 'Dashboard')->name('dashboard');
        });
});

require __DIR__.'/settings.php';
