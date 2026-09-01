<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Enums\DispositionRecipientStatus;
use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListDispositionInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('viewAnyInbox', DispositionRecipient::class);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'status' => ['sometimes', 'nullable', Rule::enum(DispositionRecipientStatus::class)],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, status: string, date_from: string, date_to: string} */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search', '')),
            'status' => trim((string) $this->validated('status', '')),
            'date_from' => trim((string) $this->validated('date_from', '')),
            'date_to' => trim((string) $this->validated('date_to', '')),
        ];
    }
}
