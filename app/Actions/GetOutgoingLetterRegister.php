<?php

namespace App\Actions;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\SubmissionSource;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\OutgoingLetters\OutgoingLetterPresenter;
use App\OutgoingLetters\OutgoingLetterScopeQuery;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;

final class GetOutgoingLetterRegister
{
    public function __construct(
        private readonly OutgoingLetterScopeQuery $scopeQuery,
        private readonly OutgoingLetterPresenter $presenter,
        private readonly OutgoingLetterPositionAssignmentResolver $outgoingAssignmentResolver,
        private readonly StandaloneOutgoingPositionAssignmentResolver $standaloneAssignmentResolver,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, source?: string|null, origin?: string|null, year?: int|string|null}  $filters
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
            'numbering_queue' => $this->numberingQueue($user),
            'sekda_approval_queue' => $this->sekdaApprovalQueue($user),
        ];
    }

    /**
     * @param  Builder<OutgoingLetter>  $query
     * @param  array{search?: string|null, status?: string|null, source?: string|null, origin?: string|null, year?: int|string|null}  $filters
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

        if (is_string($filters['origin'] ?? null) && $filters['origin'] !== '') {
            $query->where('origin', $filters['origin']);
        }

        if (is_string($filters['source'] ?? null) && $filters['source'] !== '') {
            $query
                ->where('origin', OutgoingLetterOrigin::Response->value)
                ->whereHas('incomingLetter.submission', fn (Builder $submission): Builder => $submission
                    ->where('source', SubmissionSource::from($filters['source'])->value));
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

    /** @return list<array<string, mixed>> */
    private function numberingQueue(User $user): array
    {
        if (! $user->can('outgoing-letters.number') || ! $this->outgoingAssignmentResolver->hasGeneralAffairsOfficer($user)) {
            return [];
        }

        $drafts = StandaloneOutgoingDraft::query()
            ->where('status', StandaloneOutgoingDraftStatus::AwaitingNumber)
            ->with('organizationalUnit:id,name')
            ->with('currentDocumentVersion:id,standalone_outgoing_draft_id,version_number,sha256')
            ->with(['reviews' => fn (Relation $reviews): Relation => $reviews->where('stage', 'ASSISTANT')->where('decision', 'APPROVED')])
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit(50)
            ->get();

        return array_values($drafts
            ->map(fn (StandaloneOutgoingDraft $draft): array => [
                'public_id' => $draft->public_id,
                'draft_public_id' => $draft->public_id,
                'subject' => $draft->subject,
                'recipient_name' => $draft->recipient_name,
                'recipient_organization' => $draft->recipient_organization,
                'originating_unit_name' => $draft->organizationalUnit->name,
                'current_version_number' => $draft->currentDocumentVersion->version_number,
                'sha256_fingerprint' => $draft->currentDocumentVersion->sha256,
                'approved_at' => $draft->reviews->last()?->created_at?->toISOString() ?? $draft->updated_at->toISOString(),
                'assign_number_url' => route('back-office.standalone-outgoing.assign-number', $draft),
            ])
            ->all());
    }

    /** @return list<array<string, mixed>> */
    private function sekdaApprovalQueue(User $user): array
    {
        if (! $user->can('standalone-outgoing.approve') || ! $this->standaloneAssignmentResolver->hasSekdaAssignment($user)) {
            return [];
        }

        $letters = $this->scopeQuery->visibleTo($user)
            ->where('origin', OutgoingLetterOrigin::Standalone->value)
            ->where('status', OutgoingLetterStatus::SekdaReview->value)
            ->with('standaloneDraft.organizationalUnit:id,name')
            ->with('standaloneDraft.currentDocumentVersion:id,standalone_outgoing_draft_id,sha256')
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit(50)
            ->get();

        return array_values($letters
            ->map(fn (OutgoingLetter $letter): array => [
                'public_id' => $letter->public_id,
                'subject' => $letter->subject,
                'outgoing_number' => $letter->outgoing_number,
                'letter_date' => $letter->letter_date?->toDateString(),
                'originating_unit_name' => $letter->standaloneDraft->organizationalUnit->name,
                'recipient_name' => $letter->standaloneDraft->recipient_name,
                'sha256_fingerprint' => $letter->standaloneDraft->currentDocumentVersion->sha256,
                'approval_url' => route('back-office.outgoing-letters.standalone.approvals.show', $letter),
            ])
            ->all());
    }
}
