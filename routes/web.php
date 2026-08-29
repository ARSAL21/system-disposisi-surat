<?php

use App\Authorization\AuthorizationMutationSecurity;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Http\Controllers\BackOffice\Audit\PrivilegeAuditController;
use App\Http\Controllers\BackOffice\Authorization\ActivateAuthorizationMutationController;
use App\Http\Controllers\BackOffice\Authorization\AuthorizationRoleController;
use App\Http\Controllers\BackOffice\Authorization\RolePermissionController;
use App\Http\Controllers\BackOffice\Authorization\UserRoleController;
use App\Http\Controllers\BackOffice\BackOfficeEntryController;
use App\Http\Controllers\BackOffice\Intake\IntakeSubmissionController;
use App\Http\Controllers\BackOffice\Intake\IntakeSubmissionDocumentController;
use App\Http\Controllers\BackOffice\Intake\ScreenSubmissionController;
use App\Http\Controllers\BackOffice\Organization\ActivateOrganizationMutationController;
use App\Http\Controllers\BackOffice\Organization\OrganizationalUnitController;
use App\Http\Controllers\BackOffice\Organization\OrganizationStructureController;
use App\Http\Controllers\BackOffice\Organization\PositionAssignmentController;
use App\Http\Controllers\BackOffice\Organization\PositionController;
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

                if (app()->isLocal()) {
                    Route::inertia('previews/intake-approvals', 'back-office/intake/approvals/Index')
                        ->name('previews.intake-approvals.index');
                    Route::inertia('previews/intake-approvals/{submission}', 'back-office/intake/approvals/Show')
                        ->name('previews.intake-approvals.show');
                }

                Route::prefix('intake')
                    ->name('intake.')
                    ->middleware('can:'.PermissionName::ViewIntake->value)
                    ->group(function (): void {
                        Route::get('submissions', [IntakeSubmissionController::class, 'index'])
                            ->name('submissions.index');
                        Route::get('submissions/{submission}', [IntakeSubmissionController::class, 'show'])
                            ->name('submissions.show');
                        Route::get('submissions/{submission}/document', [IntakeSubmissionDocumentController::class, 'show'])
                            ->name('submissions.document.show');
                        Route::get('submissions/{submission}/document/download', [IntakeSubmissionDocumentController::class, 'download'])
                            ->name('submissions.document.download');
                        Route::post('submissions/{submission}/screenings', ScreenSubmissionController::class)
                            ->middleware('can:'.PermissionName::ScreenIntake->value)
                            ->name('submissions.screen');
                    });

                Route::middleware('can:'.PermissionName::ViewAuthorization->value)
                    ->group(function (): void {
                        Route::redirect('authorization', '/back-office/authorization/roles')
                            ->name('authorization.legacy');
                        Route::get('authorization/roles', [AuthorizationRoleController::class, 'index'])
                            ->name('authorization.index');

                    });

                Route::get('audits/privileges', PrivilegeAuditController::class)
                    ->middleware('can:'.PermissionName::ViewPrivilegeAudits->value)
                    ->name('privilege-audits.index');

                Route::middleware('can:'.PermissionName::ViewOrganization->value)
                    ->group(function (): void {
                        Route::get('organization/structure', OrganizationStructureController::class)
                            ->name('organization.structure.index');
                        Route::get('organization/assignments', [PositionAssignmentController::class, 'index'])
                            ->name('organization.assignments.index');
                    });

                Route::middleware([
                    'mfa',
                    RequirePassword::using(
                        'back-office.password.confirm',
                        AuthorizationMutationSecurity::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS,
                    ),
                ])->group(function (): void {
                    Route::get('organization/confirm', ActivateOrganizationMutationController::class)
                        ->name('organization.mutation.confirm');
                });

                Route::middleware([
                    'can:'.PermissionName::ManageOrganization->value,
                    'mfa',
                    RequirePassword::using(
                        'back-office.password.confirm',
                        AuthorizationMutationSecurity::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS,
                    ),
                ])->group(function (): void {
                    Route::post('organization/units', [OrganizationalUnitController::class, 'store'])
                        ->name('organization.units.store');
                    Route::patch('organization/units/{organizationalUnit}', [OrganizationalUnitController::class, 'update'])
                        ->name('organization.units.update');
                    Route::patch('organization/units/{organizationalUnit}/status', [OrganizationalUnitController::class, 'status'])
                        ->name('organization.units.status');
                    Route::post('organization/positions', [PositionController::class, 'store'])
                        ->name('organization.positions.store');
                    Route::patch('organization/positions/{position}', [PositionController::class, 'update'])
                        ->name('organization.positions.update');
                    Route::patch('organization/positions/{position}/status', [PositionController::class, 'status'])
                        ->name('organization.positions.status');
                });

                Route::middleware([
                    'can:'.PermissionName::ManagePositionAssignments->value,
                    'mfa',
                    RequirePassword::using(
                        'back-office.password.confirm',
                        AuthorizationMutationSecurity::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS,
                    ),
                ])->group(function (): void {
                    Route::post('organization/positions/{position}/assignments', [PositionAssignmentController::class, 'store'])
                        ->name('organization.assignments.store');
                    Route::post('organization/positions/{position}/assignment-replacements', [PositionAssignmentController::class, 'replace'])
                        ->name('organization.assignments.replace');
                    Route::patch('organization/position-assignments/{positionAssignment}/end', [PositionAssignmentController::class, 'end'])
                        ->name('organization.assignments.end');
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
