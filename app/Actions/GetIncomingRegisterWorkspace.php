<?php

namespace App\Actions;

use App\Enums\SubmissionSource;
use App\Models\IncomingLetter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetIncomingRegisterWorkspace
{
    /**
     * @param  array{search: string, source: string, status: string, year: string, date_from: string, date_to: string}  $filters
     * @return array{
     *     letters: LengthAwarePaginator<int, IncomingLetter>,
     *     summary: array{total_letters: int, online_letters: int, manual_letters: int, received_today: int}
     * }
     */
    public function execute(array $filters): array
    {
        $baseQuery = IncomingLetter::query();
        $filteredQuery = clone $baseQuery;

        $filteredQuery
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('agenda_number', 'like', $search)
                        ->orWhere('external_letter_number', 'like', $search)
                        ->orWhere('subject', 'like', $search)
                        ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                            ->where('name', 'like', $search))
                        ->orWhereHas('submission', fn (Builder $submission): Builder => $submission
                            ->where('contact_name', 'like', $search))
                        ->orWhereHas('documents', fn (Builder $document): Builder => $document
                            ->where('original_filename', 'like', $search));
                });
            })
            ->when($filters['source'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('submission', fn (Builder $submission): Builder => $submission
                    ->where('source', $filters['source'])))
            ->when($filters['status'] !== '', fn (Builder $query): Builder => $query
                ->where('status', $filters['status']))
            ->when($filters['year'] !== '', fn (Builder $query): Builder => $query
                ->where('agenda_year', (int) $filters['year']));

        $this->applyOfficeDateRange($filteredQuery, $filters['date_from'], $filters['date_to']);

        $letters = $filteredQuery
            ->with([
                'submission:id,source,contact_name',
                'senderOrganization:id,name',
                'currentDocument',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        [$todayStart, $todayEnd] = $this->officeDayBounds();

        return [
            'letters' => $letters,
            'summary' => [
                'total_letters' => (clone $baseQuery)->count(),
                'online_letters' => (clone $baseQuery)
                    ->whereHas('submission', fn (Builder $submission): Builder => $submission
                        ->where('source', SubmissionSource::Online->value))
                    ->count(),
                'manual_letters' => (clone $baseQuery)
                    ->whereHas('submission', fn (Builder $submission): Builder => $submission
                        ->where('source', SubmissionSource::Manual->value))
                    ->count(),
                'received_today' => (clone $baseQuery)
                    ->whereBetween('received_at', [$todayStart, $todayEnd])
                    ->count(),
            ],
        ];
    }

    /** @param Builder<IncomingLetter> $query */
    private function applyOfficeDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $timezone = (string) config('letter-activity.timezone', 'Asia/Makassar');

        if ($dateFrom !== '') {
            $query->where(
                'received_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'received_at',
                '<=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->utc(),
            );
        }
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function officeDayBounds(): array
    {
        $timezone = (string) config('letter-activity.timezone', 'Asia/Makassar');
        $now = CarbonImmutable::now($timezone);

        return [$now->startOfDay()->utc(), $now->endOfDay()->utc()];
    }
}
