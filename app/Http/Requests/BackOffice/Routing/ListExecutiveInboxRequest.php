<?php

namespace App\Http\Requests\BackOffice\Routing;

use App\Models\LetterRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ListExecutiveInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('viewAnyInbox', LetterRoute::class);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, date_from: string, date_to: string} */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search', '')),
            'date_from' => trim((string) $this->validated('date_from', '')),
            'date_to' => trim((string) $this->validated('date_to', '')),
        ];
    }
}
