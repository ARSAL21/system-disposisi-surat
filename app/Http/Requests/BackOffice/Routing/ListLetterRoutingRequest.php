<?php

namespace App\Http\Requests\BackOffice\Routing;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListLetterRoutingRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('viewAnyRouting', IncomingLetter::class);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                IncomingLetterStatus::Registered->value,
                IncomingLetterStatus::Routed->value,
            ])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return array{search: string, status: string} */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search', '')),
            'status' => trim((string) $this->validated('status', '')),
        ];
    }
}
