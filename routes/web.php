<?php

use App\Authorization\AuthorizationMutationSecurity;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Http\Controllers\BackOffice\Audit\LetterActivityController;
use App\Http\Controllers\BackOffice\Audit\PrivilegeAuditController;
use App\Http\Controllers\BackOffice\Authorization\ActivateAuthorizationMutationController;
use App\Http\Controllers\BackOffice\Authorization\AuthorizationRoleController;
use App\Http\Controllers\BackOffice\Authorization\RolePermissionController;
use App\Http\Controllers\BackOffice\Authorization\UserRoleController;
use App\Http\Controllers\BackOffice\BackOfficeDashboardController;
use App\Http\Controllers\BackOffice\BackOfficeEntryController;
use App\Http\Controllers\BackOffice\Disposition\DispositionInboxController;
use App\Http\Controllers\BackOffice\Disposition\DispositionInboxDocumentController;
use App\Http\Controllers\BackOffice\Disposition\StoreForwardDispositionController;
use App\Http\Controllers\BackOffice\Disposition\StoreInitialDispositionController;
use App\Http\Controllers\BackOffice\Documents\DocumentArchiveController;
use App\Http\Controllers\BackOffice\Documents\LetterDocumentFileController;
use App\Http\Controllers\BackOffice\Documents\LetterDocumentHistoryController;
use App\Http\Controllers\BackOffice\Documents\LetterDocumentVersionController;
use App\Http\Controllers\BackOffice\Intake\IntakeApprovalController;
use App\Http\Controllers\BackOffice\Intake\IntakeApprovalDocumentController;
use App\Http\Controllers\BackOffice\Intake\IntakeSubmissionController;
use App\Http\Controllers\BackOffice\Intake\IntakeSubmissionDocumentController;
use App\Http\Controllers\BackOffice\Intake\ScreenSubmissionController;
use App\Http\Controllers\BackOffice\Intake\SubmissionDecisionController;
use App\Http\Controllers\BackOffice\Organization\ActivateOrganizationMutationController;
use App\Http\Controllers\BackOffice\Organization\OrganizationalUnitController;
use App\Http\Controllers\BackOffice\Organization\OrganizationStructureController;
use App\Http\Controllers\BackOffice\Organization\PositionAssignmentController;
use App\Http\Controllers\BackOffice\Organization\PositionController;
use App\Http\Controllers\BackOffice\Routing\ExecutiveInboxController;
use App\Http\Controllers\BackOffice\Routing\ExecutiveInboxDocumentController;
use App\Http\Controllers\BackOffice\Routing\LetterRoutingController;
use App\Http\Controllers\BackOffice\Routing\LetterRoutingDocumentController;
use App\Http\Controllers\BackOffice\Routing\StoreLetterRouteController;
use App\Http\Controllers\BackOffice\Workflow\ActivateWorkflowMutationController;
use App\Http\Controllers\BackOffice\Workflow\InstructionLabelController;
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
                ->middleware('throttle:private-document-access')
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
                Route::get('dashboard', BackOfficeDashboardController::class)->name('dashboard');

                if (app()->environment('local', 'testing')) {
                    Route::inertia('previews/dashboard', 'back-office/Dashboard', ['preview' => true])
                        ->name('previews.dashboard');
                    Route::inertia('previews/intake-approvals', 'back-office/intake/approvals/Index')
                        ->name('previews.intake-approvals.index');
                    Route::inertia('previews/intake-approvals/{submission}', 'back-office/intake/approvals/Show')
                        ->name('previews.intake-approvals.show');
                    Route::inertia('previews/letter-activities', 'back-office/letter-activities/Index')
                        ->name('previews.letter-activities.index');
                    Route::inertia('previews/letter-activities/summary', 'back-office/letter-activities/Index')
                        ->name('previews.letter-activities.summary');
                    Route::inertia('previews/documents', 'back-office/documents/Index', ['preview' => true])
                        ->name('previews.documents.index');
                    Route::inertia('previews/letters/{incomingLetter}/documents', 'back-office/letters/documents/Index', ['preview' => true])
                        ->name('previews.documents.show');
                    Route::inertia('previews/letter-routing', 'back-office/letter-routing/Index', ['preview' => true])
                        ->name('previews.letter-routing.index');
                    Route::inertia('previews/letter-routing/letters/{incomingLetter}', 'back-office/letter-routing/Show', ['preview' => true])
                        ->name('previews.letter-routing.show');
                    Route::inertia('previews/executive-inbox', 'back-office/executive/inbox/Index', ['preview' => true])
                        ->name('previews.executive-inbox.index');
                    Route::inertia('previews/executive-inbox/routes/{letterRoute}', 'back-office/executive/inbox/Show', ['preview' => true])
                        ->name('previews.executive-inbox.show');
                    Route::inertia('previews/dispositions/inbox', 'back-office/dispositions/inbox/Index', ['preview' => true])
                        ->name('previews.dispositions.inbox.index');
                    Route::inertia('previews/dispositions/inbox/recipients/{dispositionRecipient}', 'back-office/dispositions/inbox/Show', ['preview' => true])
                        ->name('previews.dispositions.inbox.show');
                    Route::inertia('previews/workflow/instruction-labels', 'back-office/workflow/instruction-labels/Index', ['preview' => true])
                        ->name('previews.workflow.instruction-labels.index');
                }

                Route::get('documents', DocumentArchiveController::class)
                    ->middleware('can:'.PermissionName::ViewDocumentVersions->value)
                    ->name('documents.index');

                Route::scopeBindings()->group(function (): void {
                    Route::get('letters/{incomingLetter}/documents', LetterDocumentHistoryController::class)
                        ->middleware('can:'.PermissionName::ViewDocumentVersions->value)
                        ->name('letters.documents.index');
                    Route::post('letters/{incomingLetter}/documents', [LetterDocumentVersionController::class, 'store'])
                        ->middleware([
                            'can:'.PermissionName::CreateDocumentVersions->value,
                            'throttle:document-version-upload',
                        ])
                        ->name('letters.documents.store');
                    Route::get('letters/{incomingLetter}/documents/{letterDocument}/preview', [LetterDocumentFileController::class, 'preview'])
                        ->middleware([
                            'can:'.PermissionName::ViewDocumentVersions->value,
                            'throttle:private-document-access',
                        ])
                        ->name('letters.documents.preview');
                    Route::get('letters/{incomingLetter}/documents/{letterDocument}/download', [LetterDocumentFileController::class, 'download'])
                        ->middleware([
                            'can:'.PermissionName::ViewDocumentVersions->value,
                            'throttle:private-document-access',
                        ])
                        ->name('letters.documents.download');
                });

                Route::prefix('letter-routing')
                    ->name('letter-routing.')
                    ->middleware('can:'.PermissionName::ViewLetterRouting->value)
                    ->group(function (): void {
                        Route::get('/', [LetterRoutingController::class, 'index'])
                            ->name('index');
                        Route::get('letters/{incomingLetter}', [LetterRoutingController::class, 'show'])
                            ->name('show');
                        Route::get('letters/{incomingLetter}/document/preview', [LetterRoutingDocumentController::class, 'preview'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.preview');
                        Route::get('letters/{incomingLetter}/document/download', [LetterRoutingDocumentController::class, 'download'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.download');
                    });

                Route::post('letter-routing/letters/{incomingLetter}', StoreLetterRouteController::class)
                    ->middleware([
                        'can:'.PermissionName::CreateLetterRouting->value,
                        'throttle:letter-routing-create',
                    ])
                    ->name('letter-routing.store');

                Route::prefix('executive/inbox')
                    ->name('executive.inbox.')
                    ->middleware('can:'.PermissionName::ViewExecutiveInbox->value)
                    ->group(function (): void {
                        Route::get('/', [ExecutiveInboxController::class, 'index'])
                            ->name('index');
                        Route::get('routes/{letterRoute}', [ExecutiveInboxController::class, 'show'])
                            ->name('show');
                        Route::get('routes/{letterRoute}/document/preview', [ExecutiveInboxDocumentController::class, 'preview'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.preview');
                        Route::get('routes/{letterRoute}/document/download', [ExecutiveInboxDocumentController::class, 'download'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.download');
                    });

                Route::post('executive/inbox/routes/{letterRoute}/dispositions', StoreInitialDispositionController::class)
                    ->middleware([
                        'can:'.PermissionName::CreateDispositions->value,
                        'throttle:disposition-create',
                    ])
                    ->name('executive.inbox.dispositions.store');

                Route::prefix('dispositions/inbox')
                    ->name('dispositions.inbox.')
                    ->middleware('can:'.PermissionName::ViewDispositions->value)
                    ->group(function (): void {
                        Route::get('/', [DispositionInboxController::class, 'index'])
                            ->name('index');
                        Route::get('recipients/{dispositionRecipient}', [DispositionInboxController::class, 'show'])
                            ->name('show');
                        Route::get('recipients/{dispositionRecipient}/document/preview', [DispositionInboxDocumentController::class, 'preview'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.preview');
                        Route::get('recipients/{dispositionRecipient}/document/download', [DispositionInboxDocumentController::class, 'download'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.download');
                        Route::post('recipients/{dispositionRecipient}/forward', StoreForwardDispositionController::class)
                            ->middleware([
                                'can:'.PermissionName::CreateDispositions->value,
                                'throttle:disposition-create',
                            ])
                            ->name('forward.store');
                    });

                Route::get('workflow/instruction-labels', [InstructionLabelController::class, 'index'])
                    ->middleware('can:'.PermissionName::ViewDispositionInstructions->value)
                    ->name('workflow.instruction-labels.index');

                Route::prefix('intake')
                    ->name('intake.')
                    ->middleware('can:'.PermissionName::ViewIntake->value)
                    ->group(function (): void {
                        Route::get('submissions', [IntakeSubmissionController::class, 'index'])
                            ->name('submissions.index');
                        Route::get('submissions/{submission}', [IntakeSubmissionController::class, 'show'])
                            ->name('submissions.show');
                        Route::get('submissions/{submission}/document', [IntakeSubmissionDocumentController::class, 'show'])
                            ->middleware('throttle:private-document-access')
                            ->name('submissions.document.show');
                        Route::get('submissions/{submission}/document/download', [IntakeSubmissionDocumentController::class, 'download'])
                            ->middleware('throttle:private-document-access')
                            ->name('submissions.document.download');
                        Route::post('submissions/{submission}/screenings', ScreenSubmissionController::class)
                            ->middleware('can:'.PermissionName::ScreenIntake->value)
                            ->name('submissions.screen');
                    });

                Route::prefix('intake/approvals')
                    ->name('intake.approvals.')
                    ->middleware('can:'.PermissionName::DecideIntake->value)
                    ->group(function (): void {
                        Route::get('/', [IntakeApprovalController::class, 'index'])
                            ->name('index');
                        Route::get('{submission}', [IntakeApprovalController::class, 'show'])
                            ->name('show');
                        Route::get('{submission}/document', [IntakeApprovalDocumentController::class, 'show'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.show');
                        Route::get('{submission}/document/download', [IntakeApprovalDocumentController::class, 'download'])
                            ->middleware('throttle:private-document-access')
                            ->name('document.download');
                        Route::post('{submission}/decisions', SubmissionDecisionController::class)
                            ->middleware('throttle:30,1')
                            ->name('decisions.store');
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

                Route::get('audits/letters', LetterActivityController::class)
                    ->middleware('can:'.PermissionName::ViewLetterActivities->value)
                    ->name('letter-activities.index');

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

                Route::middleware([
                    'can:'.PermissionName::ManageDispositionInstructions->value,
                    'mfa',
                    RequirePassword::using(
                        'back-office.password.confirm',
                        AuthorizationMutationSecurity::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS,
                    ),
                ])->group(function (): void {
                    Route::get('workflow/confirm', ActivateWorkflowMutationController::class)
                        ->name('workflow.mutation.confirm');
                    Route::post('workflow/instruction-labels', [InstructionLabelController::class, 'store'])
                        ->name('workflow.instruction-labels.store');
                    Route::patch('workflow/instruction-labels/{instructionLabel}', [InstructionLabelController::class, 'update'])
                        ->name('workflow.instruction-labels.update');
                    Route::patch('workflow/instruction-labels/{instructionLabel}/status', [InstructionLabelController::class, 'status'])
                        ->name('workflow.instruction-labels.status');
                });
            });
        });
});

require __DIR__.'/settings.php';
