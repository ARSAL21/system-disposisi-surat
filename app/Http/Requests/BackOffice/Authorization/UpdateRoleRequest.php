<?php

namespace App\Http\Requests\BackOffice\Authorization;

use App\Authorization\AuthorizationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            && $this->user()?->can('update', $role) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(AuthorizationCatalog::roleNames()),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->ignore($role->getKey()),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.regex' => 'Nama role harus berupa slug lowercase dengan pemisah tanda hubung.',
            'name.not_in' => 'Nama tersebut dilindungi oleh katalog otorisasi.',
            'name.unique' => 'Nama role sudah digunakan pada guard web.',
        ];
    }
}
