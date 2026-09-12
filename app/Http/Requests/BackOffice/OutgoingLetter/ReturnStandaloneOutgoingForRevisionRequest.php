<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class ReturnStandaloneOutgoingForRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'min:10', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['note' => trim((string) $this->input('note'))]);
    }
}
