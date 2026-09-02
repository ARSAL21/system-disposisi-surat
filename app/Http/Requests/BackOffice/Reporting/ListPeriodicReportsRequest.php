<?php

namespace App\Http\Requests\BackOffice\Reporting;

use App\Enums\IncomingLetterStatus;
use App\Enums\SubmissionSource;
use App\Models\IncomingLetter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListPeriodicReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('viewAnyReports', IncomingLetter::class);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $timezone = (string) config('letter-activity.timezone');
        $now = CarbonImmutable::now($timezone);
        $dateFrom = trim((string) $this->input('date_from', ''));
        $dateTo = trim((string) $this->input('date_to', ''));

        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = $now->startOfMonth()->toDateString();
            $dateTo = $now->endOfMonth()->toDateString();
        } elseif ($dateFrom === '') {
            $dateFrom = $dateTo;
        } elseif ($dateTo === '') {
            $dateTo = $dateFrom;
        }

        $this->merge([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'event' => trim((string) $this->input('event', 'RECEIVED')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'source' => ['sometimes', 'nullable', Rule::enum(SubmissionSource::class)],
            'event' => ['required', Rule::in(['RECEIVED', 'PROCESSING_STARTED', 'COMPLETED'])],
            'status' => ['sometimes', 'nullable', Rule::enum(IncomingLetterStatus::class)],
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['date_from', 'date_to'])) {
                return;
            }

            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('date_from'));
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('date_to'));

            if ($from !== null && $to !== null && $from->diffInDays($to) > 365) {
                $validator->errors()->add('date_to', 'Rentang laporan maksimal 366 hari inklusif.');
            }
        }];
    }

    /** @return array{date_from: string, date_to: string, source: string, event: string, status: string, search: string} */
    public function filters(): array
    {
        return [
            'date_from' => (string) $this->validated('date_from'),
            'date_to' => (string) $this->validated('date_to'),
            'source' => trim((string) $this->validated('source', '')),
            'event' => (string) $this->validated('event'),
            'status' => trim((string) $this->validated('status', '')),
            'search' => trim((string) $this->validated('search', '')),
        ];
    }
}
