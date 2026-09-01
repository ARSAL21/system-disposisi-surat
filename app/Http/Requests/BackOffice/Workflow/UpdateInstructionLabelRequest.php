<?php

namespace App\Http\Requests\BackOffice\Workflow;

use App\Models\InstructionLabel;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateInstructionLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $label = $this->route('instructionLabel');

        if (! $label instanceof InstructionLabel) {
            return false;
        }

        Gate::authorize('update', $label);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $prepared = [
            'name' => trim((string) $this->input('name', '')),
            'description' => trim((string) $this->input('description', '')),
        ];

        if ($this->has('code')) {
            $prepared['code'] = mb_strtoupper(trim((string) $this->input('code')));
        }

        $this->merge($prepared);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $label = $this->route('instructionLabel');

                    if (! $label instanceof InstructionLabel || $value !== $label->code) {
                        $fail('Kode instruksi tidak dapat diubah.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['prohibited'],
        ];
    }

    /** @return array{name: string, description: string|null, sort_order: int} */
    public function validatedPayload(): array
    {
        $description = (string) $this->validated('description', '');

        return [
            'name' => (string) $this->validated('name'),
            'description' => $description === '' ? null : $description,
            'sort_order' => (int) $this->validated('sort_order'),
        ];
    }
}
