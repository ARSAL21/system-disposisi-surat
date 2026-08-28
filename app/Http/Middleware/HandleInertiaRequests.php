<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use App\Models\User;
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
                'can_manage_position_assignments' => false,
                'can_view_privilege_audits' => false,
            ];
        }

        return [
            'can_view_authorization' => $user->can(PermissionName::ViewAuthorization->value),
            'can_manage_authorization' => $user->can(PermissionName::ManageAuthorization->value),
            'can_manage_position_assignments' => $user->can(PermissionName::ManagePositionAssignments->value),
            'can_view_privilege_audits' => $user->can(PermissionName::ViewPrivilegeAudits->value),
        ];
    }
}
