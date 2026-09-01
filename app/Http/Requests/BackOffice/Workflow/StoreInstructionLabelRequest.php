<?php

namespace App\Http\Requests\BackOffice\Workflow;

use App\Models\InstructionLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstructionLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InstructionLabel::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code', ''))),
            'name' => trim((string) $this->input('name', '')),
            'description' => trim((string) $this->input('description', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9]+(?:_[A-Z0-9]+)*$/',
                Rule::unique('instruction_labels', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{code: string, name: string, description: string|null, sort_order: int} */
    public function validatedPayload(): array
    {
        $description = (string) $this->validated('description', '');

        return [
            'code' => (string) $this->validated('code'),
            'name' => (string) $this->validated('name'),
            'description' => $description === '' ? null : $description,
            'sort_order' => (int) $this->validated('sort_order'),
        ];
    }
}
