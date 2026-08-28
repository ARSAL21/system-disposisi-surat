<?php

namespace App\Authorization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class AuthorizationMutationSecurity
{
    public const int PASSWORD_CONFIRMATION_TIMEOUT_SECONDS = 900;

    public function passwordConfirmedUntil(Request $request): ?int
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if ($confirmedAt === 0) {
            return null;
        }

        return $confirmedAt + self::PASSWORD_CONFIRMATION_TIMEOUT_SECONDS;
    }

    public function hasRecentPasswordConfirmation(Request $request): bool
    {
        $confirmedUntil = $this->passwordConfirmedUntil($request);

        return $confirmedUntil !== null && $confirmedUntil >= Date::now()->unix();
    }
}
