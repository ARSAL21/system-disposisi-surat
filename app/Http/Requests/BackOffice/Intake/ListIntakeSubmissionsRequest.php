<?php

namespace App\Http\Requests\BackOffice\Intake;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIntakeSubmissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAnyIntake', LetterSubmission::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $statuses = array_map(
            static fn (SubmissionStatus $status): string => $status->value,
            array_filter(
                SubmissionStatus::cases(),
                static fn (SubmissionStatus $status): bool => $status !== SubmissionStatus::Draft,
            ),
        );

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['all', ...$statuses])],
            'source' => ['sometimes', Rule::in([
                'all',
                SubmissionSource::Online->value,
                SubmissionSource::Manual->value,
            ])],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
