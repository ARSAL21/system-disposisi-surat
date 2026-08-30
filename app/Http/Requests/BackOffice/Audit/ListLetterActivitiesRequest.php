<?php

namespace App\Http\Requests\BackOffice\Audit;

use App\Auditing\LetterActivityCatalog;
use App\Enums\LetterActivityVisibility;
use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLetterActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewLetterActivities', AuditLog::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'nullable', 'string', Rule::in(LetterActivityCatalog::actions())],
            'source' => ['sometimes', 'nullable', 'string', Rule::in(LetterActivityCatalog::sources())],
            'actor' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'letter' => ['sometimes', 'nullable', 'string', 'max:150'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{action: string, source: string, actor: string, letter: string, date_from: string, date_to: string}
     */
    public function filters(LetterActivityVisibility $visibility): array
    {
        $dateFrom = trim((string) $this->validated('date_from', ''));
        $dateTo = trim((string) $this->validated('date_to', ''));
        $today = now((string) config('letter-activity.timezone'))->toDateString();

        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = $today;
            $dateTo = $today;
        } elseif ($dateFrom === '') {
            $dateFrom = $dateTo;
        } elseif ($dateTo === '') {
            $dateTo = $dateFrom;
        }

        $showsDetails = $visibility === LetterActivityVisibility::Details;

        return [
            'action' => trim((string) $this->validated('action', '')),
            'source' => trim((string) $this->validated('source', '')),
            'actor' => $showsDetails
                ? trim((string) $this->validated('actor', ''))
                : '',
            'letter' => $showsDetails
                ? trim((string) $this->validated('letter', ''))
                : '',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
