<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\SubmissionSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListOutgoingLettersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::enum(OutgoingLetterStatus::class)],
            'source' => ['nullable', Rule::enum(SubmissionSource::class)],
            'origin' => ['nullable', Rule::enum(OutgoingLetterOrigin::class)],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['search' => trim((string) $this->input('search', ''))]);
    }
}
