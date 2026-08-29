<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use App\Models\User;
use App\Services\IntakePositionAssignmentResolver;
use App\Services\IntakeApprovalPositionAssignmentResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user = $user instanceof User ? $user : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->userDataFor($user),
                'capabilities' => $this->capabilitiesFor($user),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Share only the authenticated identity fields required by the frontend.
     *
     * @return array<string, mixed>|null
     */
    private function userDataFor(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'account_type' => $user->account_type->value,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    /**
     * Resolve presentation capabilities without exposing roles or permissions.
     *
     * @return array<string, bool>
     */
    private function capabilitiesFor(?User $user): array
    {
        if (! $user instanceof User || ! $user->isInternalAccount()) {
            return [
                'can_view_authorization' => false,
                'can_manage_authorization' => false,
                'can_view_organization' => false,
                'can_manage_organization' => false,
                'can_manage_position_assignments' => false,
                'can_view_privilege_audits' => false,
                'can_view_intake' => false,
                'can_screen_intake' => false,
                'can_decide_intake' => false,
            ];
        }

        $hasIntakePermission = $user->can(PermissionName::ViewIntake->value)
            || $user->can(PermissionName::ScreenIntake->value);
        $hasIntakePosition = $hasIntakePermission
            && app(IntakePositionAssignmentResolver::class)->hasActiveAssignment($user);
        $hasApprovalPermission = $user->can(PermissionName::DecideIntake->value);
        $hasApprovalPosition = $hasApprovalPermission
            && app(IntakeApprovalPositionAssignmentResolver::class)->hasActiveAssignment($user);

        return [
            'can_view_authorization' => $user->can(PermissionName::ViewAuthorization->value),
            'can_manage_authorization' => $user->can(PermissionName::ManageAuthorization->value),
            'can_view_organization' => $user->can(PermissionName::ViewOrganization->value),
            'can_manage_organization' => $user->can(PermissionName::ManageOrganization->value),
            'can_manage_position_assignments' => $user->can(PermissionName::ManagePositionAssignments->value),
            'can_view_privilege_audits' => $user->can(PermissionName::ViewPrivilegeAudits->value),
            'can_view_intake' => $hasIntakePosition
                && $user->can(PermissionName::ViewIntake->value),
            'can_screen_intake' => $hasIntakePosition
                && $user->can(PermissionName::ScreenIntake->value),
            'can_decide_intake' => $hasApprovalPosition && $hasApprovalPermission,
        ];
    }
}
