<?php

namespace App\Actions\UserManagement;

use App\Enums\RoleName;
use App\Enums\SubmissionStatus;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\UserTelemetryResolver;

class FormatUserManagementItem
{
    public function __construct(
        private readonly UserTelemetryResolver $telemetryResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user, ?User $actor = null): array
    {
        $telemetry = $this->telemetryResolver->resolve($user);

        // Position assignments
        $activeAssignment = $user->activePositionAssignments->first();
        $activePositionInfo = null;
        if ($activeAssignment) {
            $pos = $activeAssignment->position;
            $unit = $pos->organizationalUnit;
            $activePositionInfo = [
                'id' => $activeAssignment->getKey(),
                'position_id' => $activeAssignment->position_id,
                'position_name' => $pos->name,
                'unit_name' => $pos->organizational_unit_id === null ? '-' : $unit->name,
                'unit_code' => $pos->organizational_unit_id === null ? '-' : $unit->code,
                'level_code' => $pos->positionLevel->code,
                'started_at' => $activeAssignment->started_at->toISOString(),
                'ended_at' => $activeAssignment->ended_at?->toISOString(),
            ];
        }

        $historicalPositions = $user->positionAssignments
            ->map(function (PositionAssignment $pa) {
                $pos = $pa->position;
                $unit = $pos->organizationalUnit;

                return [
                    'id' => $pa->getKey(),
                    'position_id' => $pa->position_id,
                    'position_name' => $pos->name,
                    'unit_name' => $pos->organizational_unit_id === null ? '-' : $unit->name,
                    'unit_code' => $pos->organizational_unit_id === null ? '-' : $unit->code,
                    'level_code' => $pos->positionLevel->code,
                    'started_at' => $pa->started_at->toISOString(),
                    'ended_at' => $pa->ended_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        // Submission metrics (Zero-leakage policy: pure aggregate numbers only)
        $submissionsQuery = $user->letterSubmissions();
        $submissionMetrics = [
            'total' => (clone $submissionsQuery)->count(),
            'draft' => (clone $submissionsQuery)->where('status', SubmissionStatus::Draft->value)->count(),
            'submitted' => (clone $submissionsQuery)->whereIn('status', [
                SubmissionStatus::Submitted->value,
                SubmissionStatus::InternalRevisionRequired->value,
                SubmissionStatus::RevisionRequired->value,
                SubmissionStatus::ReadyForApproval->value,
            ])->count(),
            'verified' => (clone $submissionsQuery)->where('status', SubmissionStatus::Registered->value)->count(),
            'rejected' => (clone $submissionsQuery)->where('status', SubmissionStatus::Rejected->value)->count(),
        ];

        // Deactivation eligibility
        $canDeactivate = true;
        $deactivationBlockReason = null;

        if ($actor && $actor->is($user)) {
            $canDeactivate = false;
            $deactivationBlockReason = 'Anda tidak dapat menonaktifkan akun sendiri.';
        } elseif ($user->hasRole(RoleName::SuperAdmin->value)) {
            $canDeactivate = false;
            $deactivationBlockReason = 'Akun dengan peran super-admin tidak dapat dinonaktifkan melalui UI.';
        } elseif ($user->isInternalAccount() && $user->activePositionAssignments()->exists()) {
            $canDeactivate = false;
            $deactivationBlockReason = 'Pengguna masih memegang jabatan aktif. Alihkan atau nonaktifkan penugasan jabatan terlebih dahulu.';
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'account_type' => $user->isInternalAccount() ? 'INTERNAL' : 'PUBLIC',
            'is_active' => (bool) $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'created_at' => $user->created_at?->toISOString() ?? now()->toISOString(),
            'updated_at' => $user->updated_at?->toISOString() ?? now()->toISOString(),
            'roles' => $user->roles->pluck('name')->all(),
            'active_position' => $activePositionInfo,
            'historical_positions' => $historicalPositions,
            'submission_metrics' => $submissionMetrics,
            'active_sessions_count' => $telemetry['active_sessions_count'],
            'last_login_at' => $telemetry['last_login_at'],
            'last_login_ip' => $telemetry['last_login_ip'],
            'last_login_device' => $telemetry['last_login_device'],
            'can_deactivate' => $canDeactivate,
            'deactivation_block_reason' => $deactivationBlockReason,
        ];
    }
}
