<?php

namespace App\Actions\UserManagement;

use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AcceptUserInvitation
{
    public function execute(
        string $publicId,
        string $rawToken,
        string $password,
        ?string $name = null,
        ?string $phoneNumber = null,
    ): User {
        $invitation = UserInvitation::query()
            ->where('public_id', $publicId)
            ->first();

        if (! $invitation || ! $invitation->isValid($rawToken)) {
            throw ValidationException::withMessages([
                'token' => ['Tautan undangan tidak valid, telah kedaluwarsa, atau sudah digunakan.'],
            ]);
        }

        if (User::query()->where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email ini sudah terdaftar dalam sistem.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $password, $name, $phoneNumber) {
            $user = new User;
            $user->name = $name ?: $invitation->name;
            $user->email = $invitation->email;
            $user->phone_number = $phoneNumber ?: $invitation->phone_number;
            $user->password = Hash::make($password);
            $user->account_type = $invitation->account_type;
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            $invitation->update([
                'accepted_at' => now(),
            ]);

            UserAccountEvent::create([
                'user_id' => $user->getKey(),
                'user_invitation_id' => $invitation->getKey(),
                'user_name' => $user->name,
                'user_email' => $user->email,
                'event_type' => UserAccountEventType::InvitationAccepted,
                'actor_user_id' => null,
                'description' => "Pengguna {$user->name} ({$user->email}) berhasil mengaktifkan akun melalui undangan.",
                'metadata' => [
                    'public_id' => $invitation->public_id,
                    'account_type' => $user->account_type->value,
                ],
                'created_at' => now(),
            ]);

            return $user;
        });
    }
}
