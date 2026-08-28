<?php

use App\Authorization\AuthorizationMutationSecurity;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Http\Controllers\BackOffice\Authorization\ActivateAuthorizationMutationController;
use App\Http\Controllers\BackOffice\Authorization\AuthorizationRoleController;
use App\Http\Controllers\BackOffice\Authorization\RolePermissionController;
use App\Http\Controllers\BackOffice\Authorization\UserRoleController;
use App\Http\Controllers\BackOffice\BackOfficeEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSubmission\LetterSubmissionController;
use App\Http\Controllers\PublicSubmission\PublicDashboardController;
use App\Http\Controllers\PublicSubmission\SubmissionDocumentController;
use App\Http\Controllers\PublicSubmission\SubmitLetterSubmissionController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('back-office')
    ->name('back-office.')
    ->group(function (): void {
        Route::get('/', BackOfficeEntryController::class)->name('entry');

        Route::middleware('back-office.guest')->group(function (): void {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

            $loginLimiter = config('fortify.limiters.login');

            Route::post('login', [AuthenticatedSessionController::class, 'store'])
                ->middleware(array_filter([
                    $loginLimiter ? 'throttle:'.$loginLimiter : null,
                ]))
                ->name('login.store');

            if (Features::enabled(Features::passkeys())) {
                $passkeyLimiter = config('fortify.limiters.passkeys');
                $passkeyMiddleware = array_filter([
                    $passkeyLimiter ? 'throttle:'.$passkeyLimiter : null,
                ]);

                Route::get('passkeys/login/options', [PasskeyLoginController::class, 'index'])
                    ->middleware($passkeyMiddleware)
                    ->name('passkey.login-options');

                Route::post('passkeys/login', [PasskeyLoginController::class, 'store'])
                    ->middleware($passkeyMiddleware)
                    ->name('passkey.login');
            }
        });
    });

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

    Route::prefix('back-office')
        ->name('back-office.')
        ->middleware('account:'.AccountType::InternalAccount->value)
        ->group(function () {
            Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');
            Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('password.confirm.store');

            Route::middleware('critical-mfa')->group(function (): void {
                Route::inertia('dashboard', 'back-office/Dashboard')->name('dashboard');

                Route::middleware('can:'.PermissionName::ViewAuthorization->value)
                    ->group(function (): void {
                        Route::redirect('authorization', '/back-office/authorization/roles')
                            ->name('authorization.legacy');
                        Route::get('authorization/roles', [AuthorizationRoleController::class, 'index'])
                            ->name('authorization.index');

                        Route::inertia('privilege-audits', 'back-office/privilege-audits/Index')
                            ->name('privilege-audits.index');
                    });

                Route::middleware([
                    'can:'.PermissionName::ManageAuthorization->value,
                    'mfa',
                    RequirePassword::using(
                        'back-office.password.confirm',
                        AuthorizationMutationSecurity::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS,
                    ),
                ])->group(function (): void {
                    Route::get('authorization/confirm', ActivateAuthorizationMutationController::class)
                        ->name('authorization.mutation.confirm');
                    Route::post('authorization/roles', [AuthorizationRoleController::class, 'store'])
                        ->name('authorization.roles.store');
                    Route::patch('authorization/roles/{role}', [AuthorizationRoleController::class, 'update'])
                        ->name('authorization.roles.update');
                    Route::delete('authorization/roles/{role}', [AuthorizationRoleController::class, 'destroy'])
                        ->name('authorization.roles.destroy');
                    Route::put('authorization/roles/{role}/permissions', [RolePermissionController::class, 'update'])
                        ->name('authorization.roles.permissions.update');
                    Route::put('authorization/users/{user}/roles', [UserRoleController::class, 'update'])
                        ->name('authorization.users.roles.update');
                });
            });
        });
});

require __DIR__.'/settings.php';
