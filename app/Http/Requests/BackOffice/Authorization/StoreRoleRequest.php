<?php

namespace App\Http\Requests\BackOffice\Authorization;

use App\Authorization\AuthorizationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(AuthorizationCatalog::roleNames()),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.regex' => 'Nama role harus berupa slug lowercase dengan pemisah tanda hubung, misalnya pengelola-surat.',
            'name.not_in' => 'Nama tersebut dilindungi oleh katalog otorisasi.',
            'name.unique' => 'Nama role sudah digunakan pada guard web.',
        ];
    }
}
