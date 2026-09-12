<?php

namespace App\Actions\UserManagement;

use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

class RevokeUserSessions
{
    public function execute(User $actor, User $target): void
    {
        if ($target->hasRole(RoleName::SuperAdmin->value)) {
            throw new LogicException('Sesi akun super-admin tidak dapat dicabut melalui antarmuka ini.');
        }

        DB::transaction(function () use ($actor, $target) {
            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            $target->forceFill(['remember_token' => null])->save();

            UserAccountEvent::create([
                'user_id' => $target->getKey(),
                'user_name' => $target->name,
                'user_email' => $target->email,
                'event_type' => UserAccountEventType::SessionsRevoked,
                'actor_user_id' => $actor->getKey(),
                'description' => "Semua sesi aktif untuk akun {$target->name} ({$target->email}) telah dicabut oleh {$actor->name}.",
                'metadata' => [
                    'sessions_cleared' => true,
                    'remember_token_cleared' => true,
                ],
                'created_at' => now(),
            ]);
        });
    }
}
