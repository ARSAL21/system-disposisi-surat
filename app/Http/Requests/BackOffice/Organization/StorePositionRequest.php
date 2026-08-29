<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Position::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code', ''))),
            'name' => trim((string) $this->input('name', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'position_level_id' => ['required', 'integer', 'exists:position_levels,id'],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9]+(?:[_-][A-Z0-9]+)*$/',
                Rule::unique('positions', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{position_level_id: int, organizational_unit_id: int|null, code: string, name: string} */
    public function validatedPayload(): array
    {
        return [
            'position_level_id' => $this->integer('position_level_id'),
            'organizational_unit_id' => $this->filled('organizational_unit_id')
                ? $this->integer('organizational_unit_id')
                : null,
            'code' => (string) $this->validated('code'),
            'name' => (string) $this->validated('name'),
        ];
    }
}
