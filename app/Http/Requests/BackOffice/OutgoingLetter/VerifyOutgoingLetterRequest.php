<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['verification_note' => ['nullable', 'string', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('verification_note');
        $this->merge(['verification_note' => is_string($note) ? trim($note) : null]);
    }
}
