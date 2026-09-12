<?php

namespace App\Http\Controllers\BackOffice\UserManagement;

use App\Actions\UserManagement\DeactivateUser;
use App\Actions\UserManagement\ReactivateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\UserManagement\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserStatusController extends Controller
{
    public function update(
        UpdateUserStatusRequest $request,
        User $managedUser,
        DeactivateUser $deactivateAction,
        ReactivateUser $reactivateAction,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($request->boolean('is_active')) {
            $reactivateAction->execute($actor, $managedUser, $request->input('reason'));
            $message = "Akun {$managedUser->name} berhasil diaktifkan kembali.";
        } else {
            $deactivateAction->execute($actor, $managedUser, (string) $request->validated('reason'));
            $message = "Akun {$managedUser->name} berhasil dinonaktifkan.";
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }
}
