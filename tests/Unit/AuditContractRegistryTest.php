<?php

use App\Auditing\Contracts\AuditActionContractRegistry;
use App\Auditing\Contracts\AuditContract;
use App\Enums\AuditAction;

it('defines an explicit contract for every single AuditAction enum case', function (): void {
    $enumCases = AuditAction::cases();
    $contracts = AuditActionContractRegistry::all();

    expect($contracts)->toHaveCount(count($enumCases));

    foreach ($enumCases as $case) {
        $contract = AuditActionContractRegistry::get($case);

        expect($contract)->toBeInstanceOf(AuditContract::class)
            ->and($contract->action)->toBe($case)
            ->and($contract->allowedSubjectTypes)->not->toBeEmpty()
            ->and($contract->domain)->not->toBeNull()
            ->and($contract->mutationType)->not->toBeNull()
            ->and($contract->positionAssignmentRequirement)->not->toBeNull();
    }
});

it('correctly maps allowed subject types', function (): void {
    $letterContract = AuditActionContractRegistry::get(AuditAction::LetterRegistered);
    expect($letterContract->allowsSubjectType('incoming_letter'))->toBeTrue()
        ->and($letterContract->allowsSubjectType('user'))->toBeFalse();

    $roleContract = AuditActionContractRegistry::get(AuditAction::RoleChanged);
    expect($roleContract->allowsSubjectType('user'))->toBeTrue()
        ->and($roleContract->allowsSubjectType('role'))->toBeTrue()
        ->and($roleContract->allowsSubjectType('letter_submission'))->toBeFalse();
});
