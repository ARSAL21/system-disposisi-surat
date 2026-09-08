<?php

namespace App\Http\Requests\BackOffice\IncomingRegister;

use App\Enums\IncomingLetterStatus;
use App\Enums\SubmissionSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIncomingRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->input('search', '')),
            'source' => trim((string) $this->input('source', '')),
            'status' => trim((string) $this->input('status', '')),
            'year' => trim((string) $this->input('year', '')),
            'date_from' => trim((string) $this->input('date_from', '')),
            'date_to' => trim((string) $this->input('date_to', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', Rule::enum(SubmissionSource::class)],
            'status' => ['nullable', Rule::enum(IncomingLetterStatus::class)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, source: string, status: string, year: string, date_from: string, date_to: string} */
    public function filters(): array
    {
        return [
            'search' => (string) $this->validated('search', ''),
            'source' => (string) $this->validated('source', ''),
            'status' => (string) $this->validated('status', ''),
            'year' => (string) $this->validated('year', ''),
            'date_from' => (string) $this->validated('date_from', ''),
            'date_to' => (string) $this->validated('date_to', ''),
        ];
    }
}
