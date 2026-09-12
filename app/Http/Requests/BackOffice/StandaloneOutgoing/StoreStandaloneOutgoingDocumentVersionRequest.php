<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStandaloneOutgoingDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'revision_note' => ['nullable', 'string', 'min:10', 'max:2000'],
        ];
    }
}
