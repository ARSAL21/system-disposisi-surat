<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class AssignOutgoingLetterNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'outgoing_number' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\pN .\-\/]+$/u'],
            'letter_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['outgoing_number' => trim((string) $this->input('outgoing_number'))]);
    }
}
