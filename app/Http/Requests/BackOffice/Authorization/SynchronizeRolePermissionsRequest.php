<?php

namespace App\Http\Requests\BackOffice\Authorization;

use App\Authorization\AuthorizationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class SynchronizeRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            && $this->user()?->can('synchronizePermissions', $role) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array', 'max:'.count(AuthorizationCatalog::permissionNames())],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(AuthorizationCatalog::permissionNames())],
        ];
    }
}
