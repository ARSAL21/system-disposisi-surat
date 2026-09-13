<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\PositionRelationship;
use App\Models\SubmissionDocument;
use App\Models\User;
use Database\Seeders\OrganizationAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('operational bootstrap seeder creates the exact organization accounts and capability roles idempotently', function (): void {
    $this->seed(OrganizationAndUserSeeder::class);

    $firstAuditCount = AuditLog::query()->count();
    $firstPasswordHash = User::query()
        ->where('email', 'petugas.surat@internal.test')
        ->value('password');

    $this->seed(OrganizationAndUserSeeder::class);

    expect(PositionLevel::query()->count())->toBe(7)
        ->and(OrganizationalUnit::query()->count())->toBe(14)
        ->and(Position::query()->count())->toBe(17)
        ->and(InstructionLabel::query()->count())->toBe(7)
        ->and(User::query()->count())->toBe(17)
        ->and(User::query()->where('account_type', AccountType::PublicAccount->value)->exists())->toBeFalse()
        ->and(PositionAssignment::query()->active()->count())->toBe(17)
        ->and(Role::query()->count())->toBe(count(RoleName::cases()))
        ->and(Permission::query()->count())->toBe(count(PermissionName::cases()))
        ->and(AuditLog::query()->count())->toBe($firstAuditCount)
        ->and(User::query()->where('email', 'petugas.surat@internal.test')->value('password'))->toBe($firstPasswordHash)
        ->and(is_string($firstPasswordHash) && Hash::check('password', $firstPasswordHash))->toBeTrue();

    foreach (RoleName::cases() as $roleName) {
        $role = Role::findByName($roleName->value, AuthorizationCatalog::GUARD_NAME);
        $actualPermissions = $role->permissions()->pluck('name')->sort()->values()->all();
        $expectedPermissions = AuthorizationCatalog::permissionsFor($roleName);
        sort($expectedPermissions);

        expect($actualPermissions)->toBe($expectedPermissions);
    }

    $expectedAccounts = [
        'wali.kota@internal.test' => [RoleName::Mayor, 'WALI_KOTA'],
        'sekda@internal.test' => [RoleName::RegionalSecretary, 'SEKDA'],
        'staf.ahli.pemerintahan@internal.test' => [RoleName::ExpertAdvisor, 'STAF_AHLI_PEMERINTAHAN_HUKUM'],
        'staf.ahli.ekonomi@internal.test' => [RoleName::ExpertAdvisor, 'STAF_AHLI_EKONOMI_PEMBANGUNAN'],
        'staf.ahli.kemasyarakatan@internal.test' => [RoleName::ExpertAdvisor, 'STAF_AHLI_KEMASYARAKATAN_SDM'],
        'asisten.1@internal.test' => [RoleName::Assistant, 'ASISTEN-I'],
        'asisten.2@internal.test' => [RoleName::Assistant, 'ASISTEN-II'],
        'asisten.3@internal.test' => [RoleName::Assistant, 'ASISTEN-III'],
        'kabag.umum@internal.test' => [RoleName::GeneralAffairsHead, 'KABAG_UMUM'],
        'kabag.kesra@internal.test' => [RoleName::SectionHead, 'KABAG_KESRA'],
        'kabag.organisasi@internal.test' => [RoleName::SectionHead, 'KABAG_ORGANISASI'],
        'kabag.tapem@internal.test' => [RoleName::SectionHead, 'KABAG_TAPEM'],
        'kabag.pembangunan@internal.test' => [RoleName::SectionHead, 'KABAG_PEMBANGUNAN'],
        'kabag.ekonomi@internal.test' => [RoleName::SectionHead, 'KABAG_EKONOMI'],
        'kabag.hukum@internal.test' => [RoleName::SectionHead, 'KABAG_HUKUM'],
        'kabag.protokoler@internal.test' => [RoleName::SectionHead, 'KABAG_PROTOCOLER'],
        'petugas.surat@internal.test' => [RoleName::LetterOfficer, 'PETUGAS'],
    ];

    foreach ($expectedAccounts as $email => [$roleName, $positionCode]) {
        $user = User::query()
            ->with(['roles', 'activePositionAssignments.position'])
            ->where('email', $email)
            ->firstOrFail();

        expect($user->isInternalAccount())->toBeTrue()
            ->and($user->is_active)->toBeTrue()
            ->and($user->hasVerifiedEmail())->toBeTrue()
            ->and($user->roles->pluck('name')->all())->toBe([$roleName->value])
            ->and($user->activePositionAssignments)->toHaveCount(1)
            ->and($user->activePositionAssignments->firstOrFail()->position->code)->toBe($positionCode);
    }

    expect(PositionRelationship::query()->count())->toBe(6);

    expect(User::role(RoleName::SuperAdmin->value)->exists())->toBeFalse()
        ->and(LetterSubmission::query()->exists())->toBeFalse()
        ->and(SubmissionDocument::query()->exists())->toBeFalse()
        ->and(IncomingLetter::query()->exists())->toBeFalse()
        ->and(LetterDocument::query()->exists())->toBeFalse()
        ->and(LetterRoute::query()->exists())->toBeFalse()
        ->and(Disposition::query()->exists())->toBeFalse()
        ->and(DispositionRecipient::query()->exists())->toBeFalse()
        ->and(DispositionFollowUp::query()->exists())->toBeFalse()
        ->and(Storage::disk('submission-documents')->allFiles())->toBe([])
        ->and(Storage::disk('letter-documents')->allFiles())->toBe([]);
});

test('operational bootstrap leaves a manually provisioned super admin untouched', function (): void {
    app(SynchronizeAuthorizationCatalog::class)->execute();
    $superAdmin = User::factory()->internal()->create([
        'name' => 'Administrator Manual',
        'email' => 'admin.manual@internal.test',
        'password' => 'different-password',
    ]);
    $superAdmin->assignRole(RoleName::SuperAdmin->value);
    $passwordHash = $superAdmin->password;

    $this->seed(OrganizationAndUserSeeder::class);

    $superAdmin->refresh();

    expect($superAdmin->name)->toBe('Administrator Manual')
        ->and($superAdmin->password)->toBe($passwordHash)
        ->and($superAdmin->roles()->pluck('name')->all())->toBe([RoleName::SuperAdmin->value])
        ->and($superAdmin->activePositionAssignments()->exists())->toBeFalse()
        ->and(User::query()->count())->toBe(18);
});

test('operational bootstrap fails closed when a deterministic email belongs to a public account', function (): void {
    User::factory()->create([
        'name' => 'Petugas Surat',
        'email' => 'petugas.surat@internal.test',
    ]);

    expect(fn () => $this->seed(OrganizationAndUserSeeder::class))
        ->toThrow(RuntimeException::class, 'conflicts with the operational bootstrap account');

    expect(User::query()->where('account_type', AccountType::PublicAccount->value)->count())->toBe(1)
        ->and(PositionAssignment::query()->exists())->toBeFalse();
});
