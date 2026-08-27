<?php

namespace App\Actions;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProvisionInternalUser
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string}  $input
     */
    public function execute(array $input): User
    {
        $input['name'] = trim($input['name']);
        $input['email'] = Str::lower(trim($input['email']));

        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($validated): User {
            $verifiedAt = Date::now();

            $user = new User;
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = $validated['password'];
            $user->email_verified_at = $verifiedAt;
            $user->account_type = AccountType::InternalAccount;
            $user->is_active = true;
            $user->save();

            $this->recordAudit->execute(
                actor: null,
                action: AuditAction::InternalAccountProvisioned,
                subjectType: 'user',
                subjectId: $user->getKey(),
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'account_type' => $user->account_type->value,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $verifiedAt->toISOString(),
                ],
                metadata: [
                    'source' => 'console',
                    'command' => 'internal:user',
                ],
            );

            return $user;
        }, attempts: 3);
    }
}
