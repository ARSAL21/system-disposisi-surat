<?php

namespace App\Actions\UserManagement;

use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Support\Facades\Password;
use LogicException;

class SendUserPasswordResetLink
{
    public function execute(User $actor, User $target): string
    {
        if ($target->hasRole(RoleName::SuperAdmin->value)) {
            throw new LogicException('Tautan reset password untuk super-admin tidak dapat dikirim melalui antarmuka ini.');
        }

        $status = Password::broker()->sendResetLink(['email' => $target->email]);

        UserAccountEvent::create([
            'user_id' => $target->getKey(),
            'user_name' => $target->name,
            'user_email' => $target->email,
            'event_type' => UserAccountEventType::PasswordResetLinkSent,
            'actor_user_id' => $actor->getKey(),
            'description' => "Tautan reset kata sandi dikirimkan ke {$target->email} oleh {$actor->name}.",
            'metadata' => [
                'broker_status' => $status,
            ],
            'created_at' => now(),
        ]);

        return $status;
    }
}
