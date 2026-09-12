<?php

namespace App\Http\Controllers\BackOffice\UserManagement;

use App\Actions\UserManagement\ResetUserTwoFactor;
use App\Actions\UserManagement\RevokeUserSessions;
use App\Actions\UserManagement\SendUserPasswordResetLink;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserSecurityController extends Controller
{
    public function revokeSessions(
        Request $request,
        User $managedUser,
        RevokeUserSessions $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('revokeSessions', $managedUser), 403);

        $action->execute($actor, $managedUser);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Semua sesi aktif untuk {$managedUser->name} berhasil dicabut.",
        ]);

        return back();
    }

    public function sendPasswordReset(
        Request $request,
        User $managedUser,
        SendUserPasswordResetLink $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('sendPasswordReset', $managedUser), 403);

        $action->execute($actor, $managedUser);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Tautan reset kata sandi telah dikirimkan ke {$managedUser->email}.",
        ]);

        return back();
    }

    public function resetTwoFactor(
        Request $request,
        User $managedUser,
        ResetUserTwoFactor $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('resetTwoFactor', $managedUser), 403);

        $action->execute($actor, $managedUser);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Autentikasi dua faktor (MFA) untuk {$managedUser->name} berhasil direset dan sesi dicabut.",
        ]);

        return back();
    }
}
