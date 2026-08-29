<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position && $this->user()?->can('update', $position) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name', ''))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['prohibited'],
            'position_level_id' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{organizational_unit_id: int|null, name: string} */
    public function validatedPayload(): array
    {
        return [
            'organizational_unit_id' => $this->filled('organizational_unit_id')
                ? $this->integer('organizational_unit_id')
                : null,
            'name' => (string) $this->validated('name'),
        ];
    }
}
