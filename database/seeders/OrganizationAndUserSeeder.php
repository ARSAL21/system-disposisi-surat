<?php

namespace Database\Seeders;

use App\Actions\RecordAudit;
use App\Actions\SynchronizeAuthorizationCatalog;
use App\Actions\SynchronizePositionLevelCatalog;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\RoleName;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrganizationAndUserSeeder extends Seeder
{
    public function __construct(
        private readonly SynchronizeAuthorizationCatalog $synchronizeAuthorizationCatalog,
        private readonly SynchronizePositionLevelCatalog $synchronizePositionLevelCatalog,
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Operational bootstrap accounts cannot be seeded in production.');
        }

        $this->synchronizeAuthorizationCatalog->execute();
        $this->synchronizePositionLevelCatalog->execute();

        try {
            DB::transaction(function (): void {
                $units = $this->seedOrganizationalUnits();
                $positions = $this->seedPositions($units);
                $this->seedInternalUsersAndAssignments($positions);
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }

    /** @return array<string, OrganizationalUnit> */
    private function seedOrganizationalUnits(): array
    {
        $units = [];

        foreach ($this->organizationalUnitDefinitions() as $code => $definition) {
            $parent = $definition['parent'] === null ? null : ($units[$definition['parent']] ?? null);

            if ($definition['parent'] !== null && $parent === null) {
                throw new RuntimeException("Parent unit {$definition['parent']} must be seeded before {$code}.");
            }

            $unit = OrganizationalUnit::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();
            $expected = [
                'code' => $code,
                'name' => $definition['name'],
                'parent_id' => $parent?->getKey(),
                'is_active' => true,
            ];

            if ($unit !== null) {
                if ($this->organizationalUnitSnapshot($unit) !== $expected) {
                    $oldValues = $this->organizationalUnitSnapshot($unit);
                    $unit->name = $definition['name'];
                    $unit->parent_id = $parent?->getKey();
                    $unit->is_active = true;
                    $unit->save();

                    $this->recordAudit->execute(
                        actor: null,
                        action: AuditAction::OrganizationalUnitUpdated,
                        subjectType: 'organizational_unit',
                        subjectId: $unit->getKey(),
                        oldValues: $oldValues,
                        newValues: $this->organizationalUnitSnapshot($unit),
                        metadata: $this->auditMetadata('organizational_unit_updated'),
                    );
                }

                $units[$code] = $unit;

                continue;
            }

            $unit = new OrganizationalUnit;
            $unit->code = $code;
            $unit->name = $definition['name'];
            $unit->parent_id = $parent?->getKey();
            $unit->is_active = true;
            $unit->save();
            $units[$code] = $unit;

            $this->recordAudit->execute(
                actor: null,
                action: AuditAction::OrganizationalUnitCreated,
                subjectType: 'organizational_unit',
                subjectId: $unit->getKey(),
                newValues: $this->organizationalUnitSnapshot($unit),
                metadata: $this->auditMetadata('organizational_unit_created'),
            );
        }

        return $units;
    }

    /**
     * @param  array<string, OrganizationalUnit>  $units
     * @return array<string, Position>
     */
    private function seedPositions(array $units): array
    {
        $levels = PositionLevel::query()
            ->whereIn('code', OrganizationCatalog::positionLevelCodes())
            ->get()
            ->keyBy('code');
        $positions = [];

        foreach ($this->positionDefinitions() as $code => $definition) {
            $unit = $units[$definition['unit']] ?? null;
            $level = $levels->get($definition['level']);

            if (! $unit instanceof OrganizationalUnit || ! $level instanceof PositionLevel) {
                throw new RuntimeException("Position dependencies for {$code} are incomplete.");
            }

            $position = Position::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();
            $expected = [
                'code' => $code,
                'name' => $definition['name'],
                'organizational_unit_id' => (int) $unit->getKey(),
                'position_level_id' => (int) $level->getKey(),
                'is_active' => true,
            ];

            if ($position !== null) {
                if ($this->positionSnapshot($position) !== $expected) {
                    $oldValues = $this->positionSnapshot($position);
                    $position->name = $definition['name'];
                    $position->organizational_unit_id = $unit->getKey();
                    $position->position_level_id = $level->getKey();
                    $position->is_active = true;
                    $position->save();

                    $this->recordAudit->execute(
                        actor: null,
                        action: AuditAction::PositionUpdated,
                        subjectType: 'position',
                        subjectId: $position->getKey(),
                        oldValues: $oldValues,
                        newValues: $this->positionSnapshot($position),
                        metadata: $this->auditMetadata('position_updated'),
                    );
                }

                $positions[$code] = $position;

                continue;
            }

            $position = new Position;
            $position->code = $code;
            $position->name = $definition['name'];
            $position->organizational_unit_id = $unit->getKey();
            $position->position_level_id = $level->getKey();
            $position->is_active = true;
            $position->save();
            $positions[$code] = $position;

            $this->recordAudit->execute(
                actor: null,
                action: AuditAction::PositionCreated,
                subjectType: 'position',
                subjectId: $position->getKey(),
                newValues: $this->positionSnapshot($position),
                metadata: $this->auditMetadata('position_created'),
            );
        }

        return $positions;
    }

    /** @param array<string, Position> $positions */
    private function seedInternalUsersAndAssignments(array $positions): void
    {
        $startedAt = Date::now();

        foreach ($this->internalUserDefinitions() as $definition) {
            $position = $positions[$definition['position']] ?? null;

            if (! $position instanceof Position) {
                throw new RuntimeException("Position {$definition['position']} is unavailable for {$definition['email']}.");
            }

            $user = $this->seedInternalUser($definition['name'], $definition['email']);
            $this->synchronizeOperationalRole($user, $definition['role']);
            $this->seedPositionAssignment($user, $position, $startedAt);
        }
    }

    private function seedInternalUser(string $name, string $email): User
    {
        $user = User::query()
            ->where('email', $email)
            ->lockForUpdate()
            ->first();

        if ($user !== null) {
            if (! $user->isInternalAccount()
                || ! $user->is_active
                || ! $user->hasVerifiedEmail()
                || $user->name !== $name
                || $user->hasRole(RoleName::SuperAdmin->value)) {
                throw new RuntimeException("Existing account {$email} conflicts with the operational bootstrap account.");
            }

            return $user;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = 'password';
        $user->email_verified_at = Date::now();
        $user->account_type = AccountType::InternalAccount;
        $user->is_active = true;
        $user->save();

        $this->recordAudit->execute(
            actor: null,
            action: AuditAction::InternalAccountProvisioned,
            subjectType: 'user',
            subjectId: $user->getKey(),
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type->value,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at->toISOString(),
            ],
            metadata: $this->auditMetadata('internal_account_provisioned'),
        );

        return $user;
    }

    private function synchronizeOperationalRole(User $user, RoleName $roleName): void
    {
        if ($roleName === RoleName::SuperAdmin) {
            throw new RuntimeException('The operational seeder cannot assign the super-admin role.');
        }

        $role = Role::query()
            ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
            ->where('name', $roleName->value)
            ->lockForUpdate()
            ->firstOrFail();
        $oldRoleNames = $this->roleNamesFor($user);
        $newRoleNames = [$roleName->value];

        if ($oldRoleNames === $newRoleNames) {
            return;
        }

        $user->syncRoles([$role]);

        $this->recordAudit->execute(
            actor: null,
            action: AuditAction::RoleChanged,
            subjectType: 'user',
            subjectId: $user->getKey(),
            oldValues: ['roles' => $oldRoleNames],
            newValues: ['roles' => $newRoleNames],
            metadata: $this->auditMetadata('operational_role_synchronized'),
        );
    }

    private function seedPositionAssignment(User $user, Position $position, CarbonInterface $startedAt): void
    {
        $activeAssignments = PositionAssignment::query()
            ->active()
            ->where(function ($query) use ($user, $position): void {
                $query->where('user_id', $user->getKey())
                    ->orWhere('position_id', $position->getKey());
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $expectedAssignment = $activeAssignments->first(
            fn (PositionAssignment $assignment): bool => (int) $assignment->user_id === (int) $user->getKey()
                && (int) $assignment->position_id === (int) $position->getKey(),
        );

        if ($expectedAssignment !== null && $activeAssignments->count() === 1) {
            return;
        }

        if ($activeAssignments->isNotEmpty()) {
            throw new RuntimeException("Active assignment for {$position->code} conflicts with {$user->email}.");
        }

        $assignment = new PositionAssignment;
        $assignment->user_id = $user->getKey();
        $assignment->position_id = $position->getKey();
        $assignment->started_at = $startedAt;
        $assignment->ended_at = null;
        $assignment->assigned_by_user_id = null;
        $assignment->save();

        $this->recordAudit->execute(
            actor: null,
            action: AuditAction::PositionAssigned,
            subjectType: 'position',
            subjectId: $position->getKey(),
            newValues: [
                'assignment_id' => $assignment->getKey(),
                'user_id' => $user->getKey(),
                'started_at' => $assignment->started_at->toISOString(),
            ],
            metadata: $this->auditMetadata('position_assigned'),
        );
    }

    /** @return list<string> */
    private function roleNamesFor(User $user): array
    {
        return array_values($user->roles()
            ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
            ->pluck('name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->sort()
            ->values()
            ->all());
    }

    /** @return array{source: string, command: string, change: string} */
    private function auditMetadata(string $change): array
    {
        return [
            'source' => 'console',
            'command' => 'db:seed',
            'change' => $change,
        ];
    }

    /** @return array{code: string, name: string, parent_id: int|null, is_active: bool} */
    private function organizationalUnitSnapshot(OrganizationalUnit $unit): array
    {
        return [
            'code' => (string) $unit->code,
            'name' => $unit->name,
            'parent_id' => $unit->parent_id === null ? null : (int) $unit->parent_id,
            'is_active' => (bool) $unit->is_active,
        ];
    }

    /** @return array{code: string, name: string, organizational_unit_id: int, position_level_id: int, is_active: bool} */
    private function positionSnapshot(Position $position): array
    {
        return [
            'code' => $position->code,
            'name' => $position->name,
            'organizational_unit_id' => (int) $position->organizational_unit_id,
            'position_level_id' => (int) $position->position_level_id,
            'is_active' => (bool) $position->is_active,
        ];
    }

    /** @return array<string, array{name: string, parent: string|null}> */
    private function organizationalUnitDefinitions(): array
    {
        return [
            'PEMKOT_BAU-BAU' => ['name' => 'Pemerintah Kota Baubau', 'parent' => null],
            'SEKDA' => ['name' => 'Sekretariat Daerah', 'parent' => 'PEMKOT_BAU-BAU'],
            'ASISTEN_1' => ['name' => 'Asisten Pemerintahan dan Kesejahteraan Rakyat (Asisten I)', 'parent' => 'SEKDA'],
            'ASISTEN_2' => ['name' => 'Asisten Perekonomian dan Pembangunan (Asisten II)', 'parent' => 'SEKDA'],
            'ASISTEN_3' => ['name' => 'Asisten Administrasi Umum (Asisten III)', 'parent' => 'SEKDA'],
            'BAGIAN_TAPEM' => ['name' => 'Bagian Tata Pemerintahan', 'parent' => 'ASISTEN_1'],
            'BAGIAN_KESRA' => ['name' => 'Bagian Kesejahteraan Rakyat', 'parent' => 'ASISTEN_1'],
            'BAGIAN_HUKUM' => ['name' => 'Bagian Hukum', 'parent' => 'ASISTEN_1'],
            'BAGIAN_EKONOMI' => ['name' => 'Bagian Ekonomi', 'parent' => 'ASISTEN_2'],
            'BAGIAN_PEMBANGUNAN' => ['name' => 'Bagian Pembangunan', 'parent' => 'ASISTEN_2'],
            'BAGIAN_UMUM' => ['name' => 'Bagian Umum', 'parent' => 'ASISTEN_3'],
            'BAGIAN_ORGANISASI' => ['name' => 'Bagian Organisasi', 'parent' => 'ASISTEN_3'],
            'BAGIAN_PROTOCOL' => ['name' => 'Bagian Protokol dan Komunikasi Pimpinan', 'parent' => 'ASISTEN_3'],
        ];
    }

    /** @return array<string, array{name: string, unit: string, level: string}> */
    private function positionDefinitions(): array
    {
        return [
            'WALI_KOTA' => ['name' => 'Wali Kota', 'unit' => 'PEMKOT_BAU-BAU', 'level' => OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL],
            'SEKDA' => ['name' => 'Sekretaris Daerah', 'unit' => 'SEKDA', 'level' => OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL],
            'ASISTEN-I' => ['name' => 'Asisten I', 'unit' => 'ASISTEN_1', 'level' => OrganizationCatalog::ASSISTANT_LEVEL],
            'ASISTEN-II' => ['name' => 'Asisten II', 'unit' => 'ASISTEN_2', 'level' => OrganizationCatalog::ASSISTANT_LEVEL],
            'ASISTEN-III' => ['name' => 'Asisten III', 'unit' => 'ASISTEN_3', 'level' => OrganizationCatalog::ASSISTANT_LEVEL],
            'KABAG_UMUM' => ['name' => 'Kepala Bagian Umum', 'unit' => 'BAGIAN_UMUM', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_KESRA' => ['name' => 'Kepala Bagian Kesejahteraan Rakyat', 'unit' => 'BAGIAN_KESRA', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_ORGANISASI' => ['name' => 'Kepala Bagian Organisasi', 'unit' => 'BAGIAN_ORGANISASI', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_TAPEM' => ['name' => 'Kepala Bagian Tata Pemerintahan', 'unit' => 'BAGIAN_TAPEM', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_PEMBANGUNAN' => ['name' => 'Kepala Bagian Pembangunan', 'unit' => 'BAGIAN_PEMBANGUNAN', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_EKONOMI' => ['name' => 'Kepala Bagian Ekonomi', 'unit' => 'BAGIAN_EKONOMI', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_HUKUM' => ['name' => 'Kepala Bagian Hukum', 'unit' => 'BAGIAN_HUKUM', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'KABAG_PROTOCOLER' => ['name' => 'Kepala Bagian Protokoler', 'unit' => 'BAGIAN_PROTOCOL', 'level' => OrganizationCatalog::SECTION_HEAD_LEVEL],
            'PETUGAS' => ['name' => 'Petugas Surat', 'unit' => 'BAGIAN_UMUM', 'level' => OrganizationCatalog::GENERAL_AFFAIRS_LEVEL],
        ];
    }

    /** @return list<array{name: string, email: string, role: RoleName, position: string}> */
    private function internalUserDefinitions(): array
    {
        return [
            ['name' => 'Wali Kota', 'email' => 'wali.kota@internal.test', 'role' => RoleName::ExecutiveLeader, 'position' => 'WALI_KOTA'],
            ['name' => 'Sekretaris Daerah', 'email' => 'sekda@internal.test', 'role' => RoleName::ExecutiveLeader, 'position' => 'SEKDA'],
            ['name' => 'Asisten I', 'email' => 'asisten.1@internal.test', 'role' => RoleName::Assistant, 'position' => 'ASISTEN-I'],
            ['name' => 'Asisten II', 'email' => 'asisten.2@internal.test', 'role' => RoleName::Assistant, 'position' => 'ASISTEN-II'],
            ['name' => 'Asisten III', 'email' => 'asisten.3@internal.test', 'role' => RoleName::Assistant, 'position' => 'ASISTEN-III'],
            ['name' => 'Kepala Bagian Umum', 'email' => 'kabag.umum@internal.test', 'role' => RoleName::GeneralAffairsHead, 'position' => 'KABAG_UMUM'],
            ['name' => 'Kepala Bagian Kesejahteraan Rakyat', 'email' => 'kabag.kesra@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_KESRA'],
            ['name' => 'Kepala Bagian Organisasi', 'email' => 'kabag.organisasi@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_ORGANISASI'],
            ['name' => 'Kepala Bagian Tata Pemerintahan', 'email' => 'kabag.tapem@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_TAPEM'],
            ['name' => 'Kepala Bagian Pembangunan', 'email' => 'kabag.pembangunan@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_PEMBANGUNAN'],
            ['name' => 'Kepala Bagian Ekonomi', 'email' => 'kabag.ekonomi@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_EKONOMI'],
            ['name' => 'Kepala Bagian Hukum', 'email' => 'kabag.hukum@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_HUKUM'],
            ['name' => 'Kepala Bagian Protokoler', 'email' => 'kabag.protokoler@internal.test', 'role' => RoleName::SectionHead, 'position' => 'KABAG_PROTOCOLER'],
            ['name' => 'Petugas Surat', 'email' => 'petugas.surat@internal.test', 'role' => RoleName::LetterOfficer, 'position' => 'PETUGAS'],
        ];
    }
}
