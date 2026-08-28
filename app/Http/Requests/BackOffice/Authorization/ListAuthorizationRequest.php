<?php

namespace App\Http\Requests\BackOffice\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ListAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Role::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tab' => ['sometimes', 'string', Rule::in(['roles', 'users'])],
            'user_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'users_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
