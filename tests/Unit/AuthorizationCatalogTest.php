<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Enums\RoleName;

test('authorization catalog exposes unique role and permission names', function (): void {
    expect(AuthorizationCatalog::roleNames())
        ->toBe([
            RoleName::SuperAdmin->value,
            RoleName::LetterOfficer->value,
            RoleName::GeneralAffairsHead->value,
            RoleName::ExecutiveLeader->value,
            RoleName::Assistant->value,
            RoleName::SectionHead->value,
        ])
        ->toHaveCount(count(array_unique(AuthorizationCatalog::roleNames())))
        ->and(AuthorizationCatalog::permissionNames())
        ->toBe([
            PermissionName::ViewAuthorization->value,
            PermissionName::ManageAuthorization->value,
            PermissionName::ViewOrganization->value,
            PermissionName::ManageOrganization->value,
            PermissionName::ManagePositionAssignments->value,
            PermissionName::ViewPrivilegeAudits->value,
            PermissionName::ViewLetterActivities->value,
            PermissionName::ViewDocumentVersions->value,
            PermissionName::CreateDocumentVersions->value,
            PermissionName::ViewLetterRouting->value,
            PermissionName::CreateLetterRouting->value,
            PermissionName::ViewExecutiveInbox->value,
            PermissionName::ViewDispositions->value,
            PermissionName::CreateDispositions->value,
            PermissionName::ProcessDispositions->value,
            PermissionName::ViewReports->value,
            PermissionName::ExportReports->value,
            PermissionName::ViewDispositionInstructions->value,
            PermissionName::ManageDispositionInstructions->value,
            PermissionName::ViewIntake->value,
            PermissionName::ScreenIntake->value,
            PermissionName::DecideIntake->value,
            PermissionName::CreateManualIntake->value,
            PermissionName::ViewIncomingRegister->value,
            PermissionName::ViewLetterResponses->value,
            PermissionName::ContributeLetterResponses->value,
            PermissionName::ReviewLetterResponses->value,
            PermissionName::AuthorizeLetterResponses->value,
            PermissionName::ViewOutgoingRegister->value,
            PermissionName::NumberOutgoingLetters->value,
            PermissionName::VerifyOutgoingLetters->value,
            PermissionName::DeliverOutgoingLetters->value,
        ])
        ->toHaveCount(count(array_unique(AuthorizationCatalog::permissionNames())));
});

test('super admin receives only the permissions explicitly listed in the catalog', function (): void {
    expect(AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin))
        ->toBe(AuthorizationCatalog::permissionNames());
});

test('operational roles expose least privilege capability bundles', function (): void {
    expect(AuthorizationCatalog::permissionsFor(RoleName::LetterOfficer))->toBe([
        PermissionName::ViewIntake->value,
        PermissionName::ScreenIntake->value,
        PermissionName::ViewLetterActivities->value,
        PermissionName::ViewDocumentVersions->value,
        PermissionName::ViewLetterRouting->value,
        PermissionName::CreateManualIntake->value,
        PermissionName::ViewIncomingRegister->value,
        PermissionName::ViewOutgoingRegister->value,
        PermissionName::NumberOutgoingLetters->value,
        PermissionName::DeliverOutgoingLetters->value,
    ])->and(AuthorizationCatalog::permissionsFor(RoleName::GeneralAffairsHead))->toBe([
        PermissionName::ViewIntake->value,
        PermissionName::DecideIntake->value,
        PermissionName::ViewLetterActivities->value,
        PermissionName::ViewDocumentVersions->value,
        PermissionName::CreateDocumentVersions->value,
        PermissionName::ViewLetterRouting->value,
        PermissionName::CreateLetterRouting->value,
        PermissionName::ViewDispositions->value,
        PermissionName::ProcessDispositions->value,
        PermissionName::ViewReports->value,
        PermissionName::ExportReports->value,
        PermissionName::ViewDispositionInstructions->value,
        PermissionName::ViewLetterResponses->value,
        PermissionName::ContributeLetterResponses->value,
        PermissionName::ViewIncomingRegister->value,
        PermissionName::ViewOutgoingRegister->value,
        PermissionName::VerifyOutgoingLetters->value,
    ])->and(AuthorizationCatalog::permissionsFor(RoleName::ExecutiveLeader))->toBe([
        PermissionName::ViewExecutiveInbox->value,
        PermissionName::CreateDispositions->value,
        PermissionName::ViewDocumentVersions->value,
        PermissionName::ViewLetterActivities->value,
        PermissionName::ViewReports->value,
        PermissionName::ExportReports->value,
        PermissionName::ViewDispositionInstructions->value,
        PermissionName::ViewLetterResponses->value,
        PermissionName::ContributeLetterResponses->value,
        PermissionName::ReviewLetterResponses->value,
        PermissionName::AuthorizeLetterResponses->value,
        PermissionName::ViewOutgoingRegister->value,
    ])->and(AuthorizationCatalog::permissionsFor(RoleName::Assistant))->toBe([
        PermissionName::ViewDispositions->value,
        PermissionName::CreateDispositions->value,
        PermissionName::ViewReports->value,
        PermissionName::ExportReports->value,
        PermissionName::ViewDispositionInstructions->value,
        PermissionName::ViewLetterResponses->value,
        PermissionName::ContributeLetterResponses->value,
        PermissionName::ReviewLetterResponses->value,
        PermissionName::ViewOutgoingRegister->value,
    ])->and(AuthorizationCatalog::permissionsFor(RoleName::SectionHead))->toBe([
        PermissionName::ViewDispositions->value,
        PermissionName::ProcessDispositions->value,
        PermissionName::ViewReports->value,
        PermissionName::ExportReports->value,
        PermissionName::ViewDispositionInstructions->value,
        PermissionName::ViewLetterResponses->value,
        PermissionName::ContributeLetterResponses->value,
        PermissionName::ViewOutgoingRegister->value,
    ]);
});

test('catalog roles are immutable while only operational roles are assignable through web', function (): void {
    foreach (RoleName::cases() as $roleName) {
        expect(AuthorizationCatalog::isProtectedRole($roleName->value))->toBeTrue();
    }

    expect(AuthorizationCatalog::isAssignableRole(RoleName::SuperAdmin->value))->toBeFalse()
        ->and(AuthorizationCatalog::isAssignableRole(RoleName::LetterOfficer->value))->toBeTrue()
        ->and(AuthorizationCatalog::isAssignableRole('custom-role'))->toBeTrue();
});
