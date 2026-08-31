<?php

namespace App\Actions;

use App\Enums\SubmissionStatus;
use App\Http\Resources\BackOffice\IntakeDashboardItemResource;
use App\Models\LetterSubmission;
use Illuminate\Http\Request;
use stdClass;

class GetBackOfficeDashboardData
{
    /**
     * @return array{
     *     metrics: array{submitted_count: int, internal_revision_count: int, ready_for_approval_count: int, registered_count: int},
     *     recent_submissions: list<array<string, mixed>>
     * }
     */
    public function execute(Request $request): array
    {
        /** @var stdClass|null $metricsRow */
        $metricsRow = LetterSubmission::query()
            ->toBase()
            ->selectRaw('
                COUNT(CASE WHEN status = ? THEN 1 END) as submitted_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as internal_revision_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as ready_for_approval_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as registered_count
            ', [
                SubmissionStatus::Submitted->value,
                SubmissionStatus::InternalRevisionRequired->value,
                SubmissionStatus::ReadyForApproval->value,
                SubmissionStatus::Registered->value,
            ])
            ->first();

        $metrics = [
            'submitted_count' => (int) ($metricsRow->submitted_count ?? 0),
            'internal_revision_count' => (int) ($metricsRow->internal_revision_count ?? 0),
            'ready_for_approval_count' => (int) ($metricsRow->ready_for_approval_count ?? 0),
            'registered_count' => (int) ($metricsRow->registered_count ?? 0),
        ];

        $submissions = LetterSubmission::query()
            ->whereIn('status', [
                SubmissionStatus::Submitted,
                SubmissionStatus::InternalRevisionRequired,
            ])
            ->with([
                'document',
                'latestDecision',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        /** @var list<array<string, mixed>> $recentSubmissions */
        $recentSubmissions = array_values(
            IntakeDashboardItemResource::collection($submissions)->resolve($request),
        );

        return [
            'metrics' => $metrics,
            'recent_submissions' => $recentSubmissions,
        ];
    }
}
