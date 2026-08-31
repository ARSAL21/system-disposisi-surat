<?php

namespace App\Http\Requests\BackOffice\Documents;

use App\Models\IncomingLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

class StoreDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incomingLetter = $this->route('incomingLetter');

        abort_unless($incomingLetter instanceof IncomingLetter, 404);
        Gate::authorize('createDocumentVersion', $incomingLetter);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                File::types(['pdf'])->max('20mb'),
                'extensions:pdf',
            ],
            'correction_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('correction_reason')) {
            $this->merge([
                'correction_reason' => trim((string) $this->input('correction_reason')),
            ]);
        }
    }
}
