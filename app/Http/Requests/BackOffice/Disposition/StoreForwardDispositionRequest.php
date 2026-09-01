<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreForwardDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispositionRecipient = $this->route('dispositionRecipient');

        if (! $dispositionRecipient instanceof DispositionRecipient) {
            return false;
        }

        Gate::authorize('forwardDisposition', $dispositionRecipient);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'instruction_note' => trim((string) $this->input('instruction_note', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'recipient_position_ids' => ['required', 'array', 'min:1', 'max:50'],
            'recipient_position_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('positions', 'id'),
            ],
            'instruction_label_ids' => ['required', 'array', 'min:1', 'max:10'],
            'instruction_label_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('instruction_labels', 'id'),
            ],
            'instruction_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return list<int> */
    public function recipientPositionIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $this->validated('recipient_position_ids'),
        ));
    }

    /** @return list<int> */
    public function instructionLabelIds(): array
    {
        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $this->validated('instruction_label_ids'),
        ));
    }

    public function instructionNote(): ?string
    {
        $note = (string) $this->validated('instruction_note', '');

        return $note === '' ? null : $note;
    }
}
