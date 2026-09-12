<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewStandaloneManualSignatureScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['note' => trim((string) $this->input('note')) ?: null]);
    }
}
