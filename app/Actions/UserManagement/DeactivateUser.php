<?php

namespace App\Actions\UserManagement;

use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DeactivateUser
{
    public function execute(User $actor, User $target, string $reason): User
    {
        if ($actor->is($target)) {
            throw new LogicException('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if ($target->hasRole(RoleName::SuperAdmin->value)) {
            throw new LogicException('Akun dengan peran super-admin tidak dapat dinonaktifkan melalui antarmuka.');
        }

        if ($target->isInternalAccount() && $target->activePositionAssignments()->exists()) {
            throw new ConflictHttpException('Pengguna masih memegang jabatan aktif. Alihkan atau nonaktifkan penugasan jabatan terlebih dahulu.');
        }

        return DB::transaction(function () use ($actor, $target, $reason) {
            $target->forceFill(['is_active' => false])->save();

            $target->syncRoles([]);

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            $target->forceFill(['remember_token' => null])->save();

            UserAccountEvent::create([
                'user_id' => $target->getKey(),
                'user_name' => $target->name,
                'user_email' => $target->email,
                'event_type' => UserAccountEventType::UserDeactivated,
                'actor_user_id' => $actor->getKey(),
                'description' => "Akun {$target->name} ({$target->email}) dinonaktifkan oleh {$actor->name}.",
                'reason' => $reason,
                'metadata' => [
                    'sessions_revoked' => true,
                    'roles_detached' => true,
                ],
                'created_at' => now(),
            ]);

            UserAccountEvent::create([
                'user_id' => $target->getKey(),
                'user_name' => $target->name,
                'user_email' => $target->email,
                'event_type' => UserAccountEventType::RolesDetached,
                'actor_user_id' => $actor->getKey(),
                'description' => 'Semua peran operasional dilepas karena penonaktifan akun.',
                'reason' => $reason,
                'metadata' => [],
                'created_at' => now(),
            ]);

            return $target;
        });
    }
}
