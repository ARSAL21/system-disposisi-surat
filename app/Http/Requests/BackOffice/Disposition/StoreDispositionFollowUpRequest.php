<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDispositionFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('dispositionRecipient');

        if (! $recipient instanceof DispositionRecipient) {
            return false;
        }

        Gate::authorize('addFollowUp', $recipient);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'note' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function note(): string
    {
        return (string) $this->validated('note');
    }
}
