<?php

namespace App\Actions\UserManagement;

use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReactivateUser
{
    public function execute(User $actor, User $target, ?string $reason = null): User
    {
        if ($target->is_active) {
            throw new LogicException('Akun ini sudah dalam status aktif.');
        }

        return DB::transaction(function () use ($actor, $target, $reason) {
            $target->forceFill(['is_active' => true])->save();

            UserAccountEvent::create([
                'user_id' => $target->getKey(),
                'user_name' => $target->name,
                'user_email' => $target->email,
                'event_type' => UserAccountEventType::UserReactivated,
                'actor_user_id' => $actor->getKey(),
                'description' => "Akun {$target->name} ({$target->email}) diaktifkan kembali oleh {$actor->name}.",
                'reason' => $reason,
                'metadata' => [
                    'least_privilege_enforced' => true,
                    'roles_restored' => false,
                ],
                'created_at' => now(),
            ]);

            return $target;
        });
    }
}
