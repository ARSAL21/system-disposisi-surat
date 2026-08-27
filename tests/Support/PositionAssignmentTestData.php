<?php

namespace Tests\Support;

use App\Authorization\AuthorizationCatalog;
use App\Enums\RoleName;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PositionAssignmentTestData
{
    private static int $sequence = 0;

    /** @param array<string, mixed> $attributes */
    public static function internalUser(array $attributes = []): User
    {
        return User::factory()->internal()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public static function position(array $attributes = []): Position
    {
        $sequence = ++self::$sequence;

        $level = new PositionLevel;
        $level->code = $attributes['level_code'] ?? "LEVEL-{$sequence}";
        $level->name = $attributes['level_name'] ?? "Level {$sequence}";
        $level->hierarchy_order = $attributes['hierarchy_order'] ?? $sequence;
        $level->is_active = true;
        $level->save();

        $position = new Position;
        $position->position_level_id = $level->getKey();
        $position->organizational_unit_id = null;
        $position->code = $attributes['code'] ?? "POSITION-{$sequence}";
        $position->name = $attributes['name'] ?? "Position {$sequence}";
        $position->is_active = $attributes['is_active'] ?? true;
        $position->save();

        return $position->refresh();
    }

    public static function assignment(
        User $user,
        Position $position,
        User $assignedBy,
        ?CarbonInterface $startedAt = null,
        ?CarbonInterface $endedAt = null,
    ): PositionAssignment {
        $assignment = new PositionAssignment;
        $assignment->user_id = $user->getKey();
        $assignment->position_id = $position->getKey();
        $assignment->started_at = $startedAt ?? Date::now()->subDay();
        $assignment->ended_at = $endedAt;
        $assignment->assigned_by_user_id = $assignedBy->getKey();
        $assignment->save();

        return $assignment->refresh();
    }

    public static function grantSuperAdminRole(User $user): void
    {
        $permissions = array_map(
            static fn (string $permission): Permission => Permission::findOrCreate(
                $permission,
                AuthorizationCatalog::GUARD_NAME,
            ),
            AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin),
        );

        $role = Role::findOrCreate(
            RoleName::SuperAdmin->value,
            AuthorizationCatalog::GUARD_NAME,
        );
        $role->syncPermissions($permissions);
        $user->assignRole($role);
    }
}
