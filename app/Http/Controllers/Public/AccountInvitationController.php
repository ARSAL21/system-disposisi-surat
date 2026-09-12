<?php

namespace App\Http\Controllers\Public;

use App\Actions\UserManagement\AcceptUserInvitation;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AcceptUserInvitationRequest;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountInvitationController extends Controller
{
    public function show(Request $request, string $publicId): Response
    {
        $rawToken = (string) $request->query('token');

        $invitation = UserInvitation::query()
            ->with('invitedBy')
            ->where('public_id', $publicId)
            ->first();

        if (! $invitation) {
            return Inertia::render('public/invitations/Accept', [
                'publicId' => $publicId,
                'token' => $rawToken,
                'preview' => false,
                'invitation' => [
                    'id' => 0,
                    'public_id' => $publicId,
                    'name' => 'Pengguna',
                    'email' => '-',
                    'phone_number' => null,
                    'account_type' => 'PUBLIC',
                    'status' => 'INVALID',
                    'expires_at' => now()->toISOString(),
                    'accepted_at' => null,
                    'revoked_at' => null,
                    'created_at' => now()->toISOString(),
                    'invited_by' => null,
                ],
            ]);
        }

        $status = 'PENDING';
        if ($invitation->accepted_at !== null) {
            $status = 'ACCEPTED';
        } elseif ($invitation->revoked_at !== null) {
            $status = 'REVOKED';
        } elseif ($invitation->expires_at < now()) {
            $status = 'EXPIRED';
        } elseif (empty($rawToken) || ! hash_equals($invitation->token_hash, hash('sha256', $rawToken))) {
            $status = 'INVALID';
        }

        return Inertia::render('public/invitations/Accept', [
            'publicId' => $publicId,
            'token' => $rawToken,
            'preview' => false,
            'invitation' => [
                'id' => $invitation->getKey(),
                'public_id' => $invitation->public_id,
                'name' => $invitation->name,
                'email' => $invitation->email,
                'phone_number' => $invitation->phone_number,
                'account_type' => $invitation->account_type === AccountType::InternalAccount ? 'INTERNAL' : 'PUBLIC',
                'status' => $status,
                'expires_at' => $invitation->expires_at->toISOString(),
                'accepted_at' => $invitation->accepted_at?->toISOString(),
                'revoked_at' => $invitation->revoked_at?->toISOString(),
                'created_at' => $invitation->created_at->toISOString(),
                'invited_by' => [
                    'id' => $invitation->invitedBy->getKey(),
                    'name' => $invitation->invitedBy->name,
                    'email' => $invitation->invitedBy->email,
                ],
            ],
        ]);
    }

    public function accept(
        AcceptUserInvitationRequest $request,
        string $publicId,
        AcceptUserInvitation $action,
    ): RedirectResponse {
        $user = $action->execute(
            $publicId,
            $request->validated('token'),
            $request->validated('password'),
            $request->validated('name'),
            $request->validated('phone_number'),
        );

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isInternalAccount()) {
            return redirect()->route('back-office.dashboard')
                ->with('status', 'Selamat datang! Akun pegawai Anda berhasil diaktifkan.');
        }

        return redirect()->route('dashboard')
            ->with('status', 'Selamat datang! Akun Anda berhasil diaktifkan.');
    }
}
