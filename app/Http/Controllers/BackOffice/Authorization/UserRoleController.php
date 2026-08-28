<?php

namespace App\Http\Controllers\BackOffice\Authorization;

use App\Actions\SynchronizeUserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Authorization\SynchronizeUserRolesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    public function update(
        SynchronizeUserRolesRequest $request,
        User $user,
        SynchronizeUserRoles $synchronizeUserRoles,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $synchronizeUserRoles->execute(
            $actor,
            $user,
            $request->validated('role_ids'),
        );

        Inertia::flash('toast', [
            'type' => $result['changed'] ? 'success' : 'info',
            'message' => $result['changed']
                ? "Assignment role {$user->name} berhasil diperbarui."
                : 'Assignment role sudah sesuai.',
        ]);

        return back();
    }
}
