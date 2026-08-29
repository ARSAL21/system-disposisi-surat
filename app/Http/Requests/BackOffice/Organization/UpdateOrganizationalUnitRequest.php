<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('organizationalUnit');

        return $unit instanceof OrganizationalUnit && $this->user()?->can('update', $unit) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name', ''))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{parent_id: int|null, name: string} */
    public function validatedPayload(): array
    {
        return [
            'parent_id' => $this->filled('parent_id') ? $this->integer('parent_id') : null,
            'name' => (string) $this->validated('name'),
        ];
    }
}
