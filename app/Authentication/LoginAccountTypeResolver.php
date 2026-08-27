<?php

namespace App\Authentication;

use App\Enums\AccountType;
use Illuminate\Http\Request;

final class LoginAccountTypeResolver
{
    public function forPasswordRequest(Request $request): ?AccountType
    {
        return match ($request->route()?->getName()) {
            'login.store' => AccountType::PublicAccount,
            'back-office.login.store' => AccountType::InternalAccount,
            default => null,
        };
    }

    public function forPasskeyRequest(Request $request): ?AccountType
    {
        return match ($request->route()?->getName()) {
            'passkey.login' => AccountType::PublicAccount,
            'back-office.passkey.login' => AccountType::InternalAccount,
            default => null,
        };
    }
}
