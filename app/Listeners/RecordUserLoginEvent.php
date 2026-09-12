<?php

namespace App\Listeners;

use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Auth\Events\Login;

class RecordUserLoginEvent
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $request = request();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        UserAccountEvent::create([
            'user_id' => $user->getKey(),
            'user_name' => $user->name,
            'user_email' => $user->email,
            'event_type' => UserAccountEventType::UserLoggedIn,
            'actor_user_id' => $user->getKey(),
            'description' => "Pengguna {$user->name} berhasil masuk.",
            'metadata' => [
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ],
            'created_at' => now(),
        ]);
    }
}
