<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;

class ChangeOrganizationalUnitStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('organizationalUnit');

        return $unit instanceof OrganizationalUnit && $this->user()?->can('changeStatus', $unit) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
