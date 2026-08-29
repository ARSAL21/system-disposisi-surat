<?php

namespace App\Actions;

use App\Authorization\AuthorizationMutationSecurity;
use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class BuildMutationSecurityState
{
    public function __construct(private readonly AuthorizationMutationSecurity $security) {}

    /**
     * @return array{
     *     can_manage: bool,
     *     mfa_enabled: bool,
     *     password_confirmed: bool,
     *     password_confirmed_until: string|null,
     *     can_mutate: bool,
     *     activation_url: string,
     *     security_settings_url: string
     * }
     */
    public function execute(Request $request, PermissionName $permission, string $activationUrl): array
    {
        /** @var User $actor */
        $actor = $request->user();
        $canManage = $actor->can($permission->value);
        $mfaEnabled = $actor->hasEnabledTwoFactorAuthentication();
        $passwordConfirmed = $this->security->hasRecentPasswordConfirmation($request);
        $confirmedUntil = $this->security->passwordConfirmedUntil($request);

        return [
            'can_manage' => $canManage,
            'mfa_enabled' => $mfaEnabled,
            'password_confirmed' => $passwordConfirmed,
            'password_confirmed_until' => $confirmedUntil === null
                ? null
                : Date::createFromTimestamp($confirmedUntil)->toISOString(),
            'can_mutate' => $canManage && $mfaEnabled && $passwordConfirmed,
            'activation_url' => $activationUrl,
            'security_settings_url' => route('security.edit'),
        ];
    }
}
