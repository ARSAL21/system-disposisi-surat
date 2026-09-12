<?php

namespace App\Actions\UserManagement;

use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResetUserTwoFactor
{
    public function execute(User $actor, User $target): void
    {
        if ($target->hasRole(RoleName::SuperAdmin->value)) {
            throw new LogicException('MFA akun super-admin tidak dapat direset melalui antarmuka ini.');
        }

        DB::transaction(function () use ($actor, $target) {
            $target->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
            ])->save();

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            UserAccountEvent::create([
                'user_id' => $target->getKey(),
                'user_name' => $target->name,
                'user_email' => $target->email,
                'event_type' => UserAccountEventType::MfaReset,
                'actor_user_id' => $actor->getKey(),
                'description' => "Autentikasi dua faktor (MFA) akun {$target->name} ({$target->email}) telah direset oleh {$actor->name}.",
                'metadata' => [
                    'mfa_cleared' => true,
                    'sessions_revoked' => true,
                ],
                'created_at' => now(),
            ]);
        });
    }
}
