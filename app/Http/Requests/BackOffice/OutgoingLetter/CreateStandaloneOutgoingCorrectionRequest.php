<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStandaloneOutgoingCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['correction_reason' => ['required', 'string', 'min:10', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('correction_reason');
        $this->merge(['correction_reason' => is_string($reason) ? trim($reason) : $reason]);
    }
}
