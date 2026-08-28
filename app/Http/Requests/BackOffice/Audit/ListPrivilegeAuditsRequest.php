<?php

namespace App\Http\Requests\BackOffice\Audit;

use App\Auditing\PrivilegeAuditCatalog;
use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPrivilegeAuditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AuditLog::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'nullable', 'string', Rule::in(PrivilegeAuditCatalog::actions())],
            'source' => ['sometimes', 'nullable', 'string', Rule::in(PrivilegeAuditCatalog::sources())],
            'actor' => ['sometimes', 'nullable', 'string', 'max:100'],
            'target_type' => ['sometimes', 'nullable', 'string', Rule::in(PrivilegeAuditCatalog::targetTypes())],
            'target' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     action: string,
     *     source: string,
     *     actor: string,
     *     target_type: string,
     *     target: string,
     *     date_from: string,
     *     date_to: string
     * }
     */
    public function filters(): array
    {
        return [
            'action' => trim((string) $this->validated('action', '')),
            'source' => trim((string) $this->validated('source', '')),
            'actor' => trim((string) $this->validated('actor', '')),
            'target_type' => trim((string) $this->validated('target_type', '')),
            'target' => trim((string) $this->validated('target', '')),
            'date_from' => trim((string) $this->validated('date_from', '')),
            'date_to' => trim((string) $this->validated('date_to', '')),
        ];
    }
}
