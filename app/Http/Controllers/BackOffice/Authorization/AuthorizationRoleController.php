<?php

namespace App\Http\Controllers\BackOffice\Authorization;

use App\Actions\CreateAuthorizationRole;
use App\Actions\DeleteAuthorizationRole;
use App\Actions\GetAuthorizationWorkspace;
use App\Actions\RenameAuthorizationRole;
use App\Authorization\AuthorizationCatalog;
use App\Authorization\AuthorizationMutationSecurity;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Authorization\DeleteRoleRequest;
use App\Http\Requests\BackOffice\Authorization\ListAuthorizationRequest;
use App\Http\Requests\BackOffice\Authorization\StoreRoleRequest;
use App\Http\Requests\BackOffice\Authorization\UpdateRoleRequest;
use App\Http\Resources\AuthorizationRoleResource;
use App\Http\Resources\AuthorizationUserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AuthorizationRoleController extends Controller
{
    public function index(
        ListAuthorizationRequest $request,
        GetAuthorizationWorkspace $getAuthorizationWorkspace,
        AuthorizationMutationSecurity $mutationSecurity,
    ): Response {
        /** @var User $actor */
        $actor = $request->user();
        $userSearch = trim((string) $request->validated('user_search', ''));
        $workspace = $getAuthorizationWorkspace->execute($userSearch !== '' ? $userSearch : null);
        $roles = AuthorizationRoleResource::collection($workspace['roles'])->resolve($request);
        $users = AuthorizationUserResource::collection($workspace['users'])
            ->response()
            ->getData(true);
        $confirmedUntil = $mutationSecurity->passwordConfirmedUntil($request);
        $mfaEnabled = $actor->hasEnabledTwoFactorAuthentication();
        $passwordConfirmed = $mutationSecurity->hasRecentPasswordConfirmation($request);

        return Inertia::render('back-office/authorization/Index', [
            'roles' => $roles,
            'permissions' => AuthorizationCatalog::permissionDefinitions(),
            'users' => $users,
            'filters' => [
                'tab' => $request->validated('tab', 'roles'),
                'user_search' => $userSearch,
            ],
            'summary' => [
                'roles' => count($roles),
                'custom_roles' => collect($roles)->where('is_protected', false)->count(),
                'permissions' => count(AuthorizationCatalog::permissionNames()),
                'internal_users' => $workspace['users']->total(),
            ],
            'mutationSecurity' => [
                'can_manage' => $actor->can(PermissionName::ManageAuthorization->value),
                'mfa_enabled' => $mfaEnabled,
                'password_confirmed' => $passwordConfirmed,
                'password_confirmed_until' => $confirmedUntil === null
                    ? null
                    : Date::createFromTimestamp($confirmedUntil)->toISOString(),
                'can_mutate' => $actor->can(PermissionName::ManageAuthorization->value)
                    && $mfaEnabled
                    && $passwordConfirmed,
                'activation_url' => route('back-office.authorization.mutation.confirm'),
                'security_settings_url' => route('security.edit'),
            ],
            'routes' => [
                'index' => route('back-office.authorization.index'),
                'store' => route('back-office.authorization.roles.store'),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request, CreateAuthorizationRole $createRole): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $role = $createRole->execute($actor, $request->validated('name'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Role {$role->name} berhasil dibuat.",
        ]);

        return to_route('back-office.authorization.index');
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        RenameAuthorizationRole $renameRole,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $renameRole->execute($actor, $role, $request->validated('name'));

        Inertia::flash('toast', [
            'type' => $result['changed'] ? 'success' : 'info',
            'message' => $result['changed'] ? 'Nama role berhasil diperbarui.' : 'Tidak ada perubahan nama role.',
        ]);

        return back();
    }

    public function destroy(
        DeleteRoleRequest $request,
        Role $role,
        DeleteAuthorizationRole $deleteRole,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $roleName = $role->name;
        $deleteRole->execute($actor, $role);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Role {$roleName} berhasil dihapus.",
        ]);

        return to_route('back-office.authorization.index');
    }
}
