<?php

namespace App\Http\Requests\BackOffice\Authorization;

use App\Authorization\AuthorizationCatalog;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SynchronizeUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && $this->user()?->can('synchronizeRoles', $target) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(config('permission.table_names.roles'), 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                        ->whereNotIn('name', AuthorizationCatalog::roleNames())),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role_ids.*.exists' => 'Salah satu role tidak tersedia untuk assignment melalui web.',
        ];
    }
}
