<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CompleteDispositionBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('dispositionRecipient');

        if (! $recipient instanceof DispositionRecipient) {
            return false;
        }

        Gate::authorize('completeBranch', $recipient);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'completion_note' => trim((string) $this->input('completion_note', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'completion_note' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function completionNote(): string
    {
        return (string) $this->validated('completion_note');
    }
}
