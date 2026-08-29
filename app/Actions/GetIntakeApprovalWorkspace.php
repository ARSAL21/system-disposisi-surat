<?php

namespace App\Actions;

use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GetIntakeApprovalWorkspace
{
    /**
     * @param array{tab: string, search: string, date_from: string, date_to: string} $filters
     * @return array{
     *     submissions: LengthAwarePaginator<int, LetterSubmission>,
     *     summary: array{awaiting_decision: int, returned_to_staff: int, registered: int, rejected: int}
     * }
     */
    public function execute(array $filters): array
    {
        $visibleStatuses = [
            SubmissionStatus::ReadyForApproval->value,
            SubmissionStatus::InternalRevisionRequired->value,
            SubmissionStatus::Registered->value,
            SubmissionStatus::Rejected->value,
        ];
        $baseQuery = LetterSubmission::query()
            ->whereIn('status', $visibleStatuses);

        $submissions = (clone $baseQuery)
            ->with([
                'document',
                'latestReview.createdBy',
                'latestDecision.createdBy',
                'incomingLetter.senderOrganization',
            ])
            ->whereIn('status', $filters['tab'] === 'pending'
                ? [SubmissionStatus::ReadyForApproval->value]
                : [
                    SubmissionStatus::InternalRevisionRequired->value,
                    SubmissionStatus::Registered->value,
                    SubmissionStatus::Rejected->value,
                ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn (Builder $query) => $query
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('sender_organization_name', 'like', "%{$search}%")
                    ->orWhere('external_letter_number', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%"));
            })
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query
                ->whereDate('submitted_at', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query
                ->whereDate('submitted_at', '<=', $date))
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return [
            'submissions' => $submissions,
            'summary' => [
                'awaiting_decision' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::ReadyForApproval->value)
                    ->count(),
                'returned_to_staff' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::InternalRevisionRequired->value)
                    ->count(),
                'registered' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::Registered->value)
                    ->count(),
                'rejected' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::Rejected->value)
                    ->count(),
            ],
        ];
    }
}
