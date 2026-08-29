<?php

namespace App\Actions;

use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\SubmissionReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GetIntakeSubmissionWorkspace
{
    /**
     * @param  array{search: string, status: string, source: string, date_from: string, date_to: string}  $filters
     * @return array{
     *     submissions: LengthAwarePaginator<int, LetterSubmission>,
     *     summary: array{awaiting_screening: int, revision_required: int, ready_for_approval: int, processed_today: int}
     * }
     */
    public function execute(array $filters): array
    {
        $baseQuery = LetterSubmission::query()
            ->where('status', '!=', SubmissionStatus::Draft->value);

        $submissions = (clone $baseQuery)
            ->with(['document', 'latestReview', 'latestDecision'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn (Builder $query) => $query
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('sender_organization_name', 'like', "%{$search}%")
                    ->orWhere('external_letter_number', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%"));
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query
                ->where('status', $filters['status']))
            ->when($filters['source'] !== 'all', fn (Builder $query) => $query
                ->where('source', $filters['source']))
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
                'awaiting_screening' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::Submitted->value)
                    ->count(),
                'revision_required' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::RevisionRequired->value)
                    ->count(),
                'ready_for_approval' => (clone $baseQuery)
                    ->where('status', SubmissionStatus::ReadyForApproval->value)
                    ->count(),
                'processed_today' => SubmissionReview::query()
                    ->whereDate('created_at', today())
                    ->count(),
            ],
        ];
    }
}
