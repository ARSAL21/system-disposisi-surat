<?php

namespace App\Http\Requests\BackOffice\UserManagement;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('invite', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $emailRule = app()->isProduction() ? 'email:rfc,dns' : 'email:rfc';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', $emailRule, 'max:255', Rule::unique(User::class, 'email')],
            'account_type' => ['required', Rule::enum(AccountType::class)],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
