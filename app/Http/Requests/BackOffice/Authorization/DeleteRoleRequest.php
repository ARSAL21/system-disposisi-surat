<?php

namespace App\Http\Requests\BackOffice\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class DeleteRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            && $this->user()?->can('delete', $role) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
