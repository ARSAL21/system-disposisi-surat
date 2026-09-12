<?php

namespace App\Actions\UserManagement;

use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use LogicException;

class RevokeUserInvitation
{
    public function execute(User $actor, UserInvitation $invitation, ?string $reason = null): UserInvitation
    {
        if ($invitation->accepted_at !== null) {
            throw new LogicException('Undangan yang telah diterima tidak dapat dibatalkan.');
        }

        if ($invitation->revoked_at !== null) {
            return $invitation;
        }

        return DB::transaction(function () use ($actor, $invitation, $reason) {
            $invitation->update([
                'revoked_at' => now(),
            ]);

            UserAccountEvent::create([
                'user_invitation_id' => $invitation->getKey(),
                'user_name' => $invitation->name,
                'user_email' => $invitation->email,
                'event_type' => UserAccountEventType::InvitationRevoked,
                'actor_user_id' => $actor->getKey(),
                'description' => "Undangan akun untuk {$invitation->email} telah dibatalkan.",
                'reason' => $reason,
                'metadata' => [
                    'public_id' => $invitation->public_id,
                ],
                'created_at' => now(),
            ]);

            return $invitation;
        });
    }
}
