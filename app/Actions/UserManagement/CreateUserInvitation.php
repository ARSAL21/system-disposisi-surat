<?php

namespace App\Actions\UserManagement;

use App\Enums\AccountType;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUserInvitation
{
    /**
     * @return array{invitation: UserInvitation, raw_token: string}
     */
    public function execute(
        User $actor,
        string $email,
        string $name,
        AccountType $accountType,
        ?string $phoneNumber = null,
    ): array {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $publicId = (string) Str::ulid();

        return DB::transaction(function () use ($actor, $email, $name, $accountType, $phoneNumber, $rawToken, $tokenHash, $publicId) {
            $normalizedEmail = strtolower(trim($email));

            // Revoke any previous pending invitation for this email
            UserInvitation::query()
                ->where('normalized_email', $normalizedEmail)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $invitation = UserInvitation::create([
                'public_id' => $publicId,
                'invited_by_user_id' => $actor->getKey(),
                'normalized_email' => $normalizedEmail,
                'name' => $name,
                'phone_number' => $phoneNumber,
                'account_type' => $accountType,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addHours(48),
            ]);

            UserAccountEvent::create([
                'user_invitation_id' => $invitation->getKey(),
                'user_name' => $name,
                'user_email' => $email,
                'event_type' => UserAccountEventType::InvitationCreated,
                'actor_user_id' => $actor->getKey(),
                'description' => "Undangan akun {$accountType->value} dibuat untuk {$email}.",
                'metadata' => [
                    'account_type' => $accountType->value,
                    'public_id' => $publicId,
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
