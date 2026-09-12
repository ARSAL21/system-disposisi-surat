<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\LetterRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreMayorDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letterRoute = $this->route('letterRoute');

        if (! $letterRoute instanceof LetterRoute) {
            return false;
        }

        Gate::authorize('forwardToSekda', $letterRoute);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['instruction_note' => trim((string) $this->input('instruction_note', ''))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'instruction_label_ids' => ['required', 'array', 'min:1', 'max:10'],
            'instruction_label_ids.*' => ['required', 'integer', 'distinct', Rule::exists('instruction_labels', 'id')],
            'instruction_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return list<int> */
    public function instructionLabelIds(): array
    {
        return array_values(array_map(static fn (mixed $id): int => (int) $id, (array) $this->validated('instruction_label_ids')));
    }

    public function instructionNote(): ?string
    {
        $note = (string) $this->validated('instruction_note', '');

        return $note === '' ? null : $note;
    }
}
