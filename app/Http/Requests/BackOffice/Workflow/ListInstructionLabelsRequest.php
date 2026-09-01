<?php

namespace App\Http\Requests\BackOffice\Workflow;

use App\Models\InstructionLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListInstructionLabelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('viewAny', InstructionLabel::class);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'all'])],
        ];
    }

    /** @return array{search: string, status: string} */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search', '')),
            'status' => (string) $this->validated('status', 'all'),
        ];
    }
}
