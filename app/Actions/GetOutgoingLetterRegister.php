<?php

namespace App\Actions;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\OutgoingLetters\OutgoingLetterPresenter;
use App\OutgoingLetters\OutgoingLetterScopeQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;

final class GetOutgoingLetterRegister
{
    public function __construct(
        private readonly OutgoingLetterScopeQuery $scopeQuery,
        private readonly OutgoingLetterPresenter $presenter,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, source?: string|null, year?: int|string|null}  $filters
     * @return array<string, mixed>
     */
    public function execute(User $user, array $filters): array
    {
        $scope = $this->scopeQuery->visibleTo($user);
        $summaryRow = (clone $scope)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as awaiting_number', [OutgoingLetterStatus::Authorized->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as awaiting_verification', [OutgoingLetterStatus::SignedDocumentUploaded->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ready_for_delivery', [OutgoingLetterStatus::AdminVerified->value])
            ->selectRaw('SUM(CASE WHEN status = ? AND updated_at >= ? AND updated_at < ? THEN 1 ELSE 0 END) as delivered_this_month', [
                OutgoingLetterStatus::Delivered->value,
                Date::now()->startOfMonth(),
                Date::now()->addMonthNoOverflow()->startOfMonth(),
            ])
            ->toBase()
            ->first();
        $query = clone $scope;
        $this->applyFilters($query, $filters);

        /** @var LengthAwarePaginator<int, OutgoingLetter> $paginator */
        $paginator = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $items = $paginator->getCollection()->map(
            fn (OutgoingLetter $letter): array => $this->presenter->listItem($letter),
        )->values()->all();

        return [
            'letters' => [
                'data' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem() ?? 0,
                    'to' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total(),
                    'previous_url' => $paginator->previousPageUrl(),
                    'next_url' => $paginator->nextPageUrl(),
                ],
            ],
            'summary' => [
                'total' => (int) ($summaryRow->total ?? 0),
                'awaiting_number' => (int) ($summaryRow->awaiting_number ?? 0),
                'awaiting_verification' => (int) ($summaryRow->awaiting_verification ?? 0),
                'ready_for_delivery' => (int) ($summaryRow->ready_for_delivery ?? 0),
                'delivered_this_month' => (int) ($summaryRow->delivered_this_month ?? 0),
            ],
        ];
    }

    /**
     * @param  Builder<OutgoingLetter>  $query
     * @param  array{search?: string|null, status?: string|null, source?: string|null, year?: int|string|null}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $searchQuery
                    ->where('subject', 'like', $like)
                    ->orWhere('outgoing_number', 'like', $like)
                    ->orWhereHas('incomingLetter', fn (Builder $letter): Builder => $letter
                        ->where('agenda_number', 'like', $like)
                        ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                            ->where('name', 'like', $like)));
            });
        }

        if (is_string($filters['status'] ?? null) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (is_string($filters['source'] ?? null) && $filters['source'] !== '') {
            $query->whereHas('incomingLetter.submission', fn (Builder $submission): Builder => $submission
                ->where('source', $filters['source']));
        }

        if (($filters['year'] ?? null) !== null && $filters['year'] !== '') {
            $year = (int) $filters['year'];
            $query->where(function (Builder $yearQuery) use ($year): void {
                $yearQuery->where('agenda_year', $year)
                    ->orWhere(function (Builder $unassigned) use ($year): void {
                        $unassigned
                            ->whereNull('agenda_year')
                            ->whereYear('authorized_at', $year);
                    });
            });
        }
    }
}
