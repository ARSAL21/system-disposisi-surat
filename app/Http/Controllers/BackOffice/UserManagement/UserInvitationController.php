<?php

namespace App\Http\Controllers\BackOffice\UserManagement;

use App\Actions\UserManagement\CreateUserInvitation;
use App\Actions\UserManagement\ResendUserInvitation;
use App\Actions\UserManagement\RevokeUserInvitation;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\UserManagement\StoreUserInvitationRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserInvitationController extends Controller
{
    public function store(
        StoreUserInvitationRequest $request,
        CreateUserInvitation $action,
    ): RedirectResponse {
        $actor = $request->user();

        $result = $action->execute(
            $actor,
            $request->validated('email'),
            $request->validated('name'),
            AccountType::from($request->validated('account_type')),
            $request->validated('phone_number'),
        );

        $invitation = $result['invitation'];
        $rawToken = $result['raw_token'];
        $invitationLink = route('account-invitations.show', [
            'publicId' => $invitation->public_id,
            'token' => $rawToken,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Undangan berhasil dibuat untuk {$invitation->email}.",
            'invitation_link' => $invitationLink,
        ]);

        return back()->with('invitation_created', [
            'public_id' => $invitation->public_id,
            'link' => $invitationLink,
            'email' => $invitation->email,
        ]);
    }

    public function resend(
        Request $request,
        UserInvitation $userInvitation,
        ResendUserInvitation $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can(PermissionName::InviteUsers->value), 403);

        $result = $action->execute($actor, $userInvitation);
        $rawToken = $result['raw_token'];
        $invitationLink = route('account-invitations.show', [
            'publicId' => $userInvitation->public_id,
            'token' => $rawToken,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Undangan untuk {$userInvitation->email} telah dikirim ulang dan masa berlaku diperpanjang.",
            'invitation_link' => $invitationLink,
        ]);

        return back()->with('invitation_resent', [
            'public_id' => $userInvitation->public_id,
            'link' => $invitationLink,
            'email' => $userInvitation->email,
        ]);
    }

    public function revoke(
        Request $request,
        UserInvitation $userInvitation,
        RevokeUserInvitation $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can(PermissionName::InviteUsers->value), 403);

        $action->execute($actor, $userInvitation, $request->input('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Undangan untuk {$userInvitation->email} berhasil dibatalkan.",
        ]);

        return back();
    }
}
