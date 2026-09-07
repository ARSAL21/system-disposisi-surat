<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class ReturnOutgoingLetterDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['revision_reason' => ['required', 'string', 'min:10', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['revision_reason' => trim((string) $this->input('revision_reason'))]);
    }
}
