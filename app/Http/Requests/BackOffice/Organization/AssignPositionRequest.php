<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Enums\AccountType;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('position') instanceof Position
            && $this->user()?->can('assign', PositionAssignment::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('account_type', AccountType::InternalAccount->value)
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at')),
            ],
            'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'],
        ];
    }
}
