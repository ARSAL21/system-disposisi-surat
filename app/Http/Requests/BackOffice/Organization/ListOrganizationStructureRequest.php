<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrganizationStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', OrganizationalUnit::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'section' => ['sometimes', Rule::in(['levels', 'units', 'positions'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['all', 'active', 'inactive'])],
            'position_level_id' => ['sometimes', 'nullable', 'integer', 'exists:position_levels,id'],
            'organizational_unit_id' => ['sometimes', 'nullable', 'integer', 'exists:organizational_units,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
