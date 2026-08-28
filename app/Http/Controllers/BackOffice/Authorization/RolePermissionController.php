<?php

namespace App\Http\Controllers\BackOffice\Authorization;

use App\Actions\SynchronizeRolePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Authorization\SynchronizeRolePermissionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function update(
        SynchronizeRolePermissionsRequest $request,
        Role $role,
        SynchronizeRolePermissions $synchronizeRolePermissions,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $synchronizeRolePermissions->execute(
            $actor,
            $role,
            $request->validated('permissions'),
        );

        Inertia::flash('toast', [
            'type' => $result['changed'] ? 'success' : 'info',
            'message' => $result['changed']
                ? 'Permission role berhasil disinkronkan.'
                : 'Mapping permission sudah sesuai.',
        ]);

        return back();
    }
}
