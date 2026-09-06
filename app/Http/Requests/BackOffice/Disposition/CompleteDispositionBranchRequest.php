<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

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
            'technical_document_note' => $this->has('technical_document_note')
                ? trim((string) $this->input('technical_document_note'))
                : null,
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'completion_note' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
            'technical_document' => ['nullable', File::types(['pdf'])->max('20mb'), 'extensions:pdf'],
            'technical_document_note' => ['exclude_without:technical_document', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function completionNote(): string
    {
        return (string) $this->validated('completion_note');
    }

    public function technicalDocument(): ?UploadedFile
    {
        $file = $this->file('technical_document');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function technicalDocumentNote(): ?string
    {
        $note = $this->validated('technical_document_note');

        return is_string($note) ? $note : null;
    }
}
