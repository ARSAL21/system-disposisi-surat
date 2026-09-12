<?php

namespace App\Actions\UserManagement;

use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResendUserInvitation
{
    /**
     * @return array{invitation: UserInvitation, raw_token: string}
     */
    public function execute(User $actor, UserInvitation $invitation): array
    {
        if ($invitation->accepted_at !== null) {
            throw new LogicException('Undangan yang telah diterima tidak dapat dikirim ulang.');
        }

        if ($invitation->revoked_at !== null) {
            throw new LogicException('Undangan yang telah dibatalkan tidak dapat dikirim ulang.');
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        return DB::transaction(function () use ($actor, $invitation, $rawToken, $tokenHash) {
            $invitation->update([
                'token_hash' => $tokenHash,
                'expires_at' => now()->addHours(48),
            ]);

            UserAccountEvent::create([
                'user_invitation_id' => $invitation->getKey(),
                'user_name' => $invitation->name,
                'user_email' => $invitation->email,
                'event_type' => UserAccountEventType::InvitationResent,
                'actor_user_id' => $actor->getKey(),
                'description' => "Undangan akun diperbarui dan dikirim ulang untuk {$invitation->email}.",
                'metadata' => [
                    'account_type' => $invitation->account_type->value,
                    'public_id' => $invitation->public_id,
                    'expires_at' => $invitation->expires_at->toISOString(),
                ],
                'created_at' => now(),
            ]);

            return [
                'invitation' => $invitation,
                'raw_token' => $rawToken,
            ];
        });
    }
}
