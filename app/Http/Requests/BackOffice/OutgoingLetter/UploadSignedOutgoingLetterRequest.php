<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class UploadSignedOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signed_document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:20480'],
            'upload_note' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['upload_note' => trim((string) $this->input('upload_note'))]);
    }
}
