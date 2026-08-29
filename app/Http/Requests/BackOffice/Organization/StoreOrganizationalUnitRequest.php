<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrganizationalUnit::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $code = trim((string) $this->input('code', ''));
        $this->merge([
            'code' => $code === '' ? null : mb_strtoupper($code),
            'name' => trim((string) $this->input('name', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+(?:[_-][A-Z0-9]+)*$/',
                Rule::unique('organizational_units', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{parent_id: int|null, code: string|null, name: string} */
    public function validatedPayload(): array
    {
        $code = $this->validated('code');

        return [
            'parent_id' => $this->filled('parent_id') ? $this->integer('parent_id') : null,
            'code' => is_string($code) ? $code : null,
            'name' => (string) $this->validated('name'),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => 'Kode unit hanya boleh berisi huruf kapital, angka, garis bawah, atau tanda hubung.',
        ];
    }
}
