<?php

namespace App\Reporting;

use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterRoute;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PeriodicReportPresenter
{
    public function __construct(
        private readonly ReportScopeResolver $scopeResolver,
    ) {}

    /**
     * @param  Collection<int, IncomingLetter>  $letters
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}|null  $filters
     * @return list<array<string, mixed>>
     */
    public function letters(Collection $letters, User $user, ?array $filters = null): array
    {
        $participantCodes = $this->participantPositionCodes($letters, $user);

        return array_values($letters->map(fn (IncomingLetter $letter): array => $this->letter(
            $letter,
            $participantCodes[(int) $letter->getKey()] ?? [],
            $filters,
        ))->all());
    }

    /** @return array<string, mixed> */
    public function detail(IncomingLetter $letter): array
    {
        $route = $letter->currentRoute;

        if (! $route instanceof LetterRoute) {
            return [
                'letter' => $this->detailLetter($letter, null),
                'initial_route' => null,
                'sekda_handoff' => null,
                'branches' => [],
                'progress' => $this->branchProgress([]),
                'visibility_note' => 'Surat belum diarahkan kepada pimpinan.',
            ];
        }

        $firstDisposition = $route->disposition;
        $sekdaHandoff = null;
        $assistantCreatorPositionId = (int) $route->recipient_position_id;

        if ($firstDisposition instanceof Disposition) {
            $sekdaRecipient = $firstDisposition->recipients->first(function (DispositionRecipient $recipient): bool {
                return $recipient->recipientPosition->positionLevel->code === OrganizationCatalog::REGIONAL_SECRETARY_LEVEL;
            });

            if ($firstDisposition->recipients->count() === 1
                && $sekdaRecipient instanceof DispositionRecipient) {
                if ($sekdaRecipient->received_at === null) {
                    throw DispositionStateConflict::inconsistentGraph();
                }

                if ($sekdaRecipient->childDispositions->count() > 1) {
                    throw DispositionStateConflict::inconsistentGraph();
                }

                $forwardedDisposition = $sekdaRecipient->childDispositions->first();
                $assistantCreatorPositionId = (int) $sekdaRecipient->recipient_position_id;
                $sekdaHandoff = [
                    'reference' => 'sekda-recipient-'.$sekdaRecipient->getKey(),
                    'recipient_position' => $this->position($sekdaRecipient->recipientPosition),
                    'status' => $sekdaRecipient->status->value,
                    'received_at' => $sekdaRecipient->received_at->toISOString(),
                    'forwarded_at' => $forwardedDisposition?->created_at?->toISOString(),
                    'instructions' => $this->instructions($firstDisposition->instructionLabels),
                    'instruction_note' => $firstDisposition->instruction_note,
                    'disposed_by' => $this->dispositionActor(
                        $firstDisposition,
                        (int) $route->recipient_position_id,
                    ),
                    'disposed_at' => $firstDisposition->created_at->toISOString(),
                ];
                $firstDisposition = $forwardedDisposition;
            }
        }

        $branches = $firstDisposition instanceof Disposition
            ? array_values($firstDisposition->recipients
                ->map(fn (DispositionRecipient $recipient): array => $this->assistantBranch(
                    $recipient,
                    $firstDisposition,
                    $assistantCreatorPositionId,
                ))
                ->all())
            : [];
        $terminalBranches = [];

        foreach ($branches as $branch) {
            foreach ($branch['children'] as $child) {
                $terminalBranches[] = $child;
            }
        }
        $completedAt = $letter->status->value === 'COMPLETED'
            ? $this->timestampString($letter->getAttribute('report_completed_at'))
            : null;

        return [
            'letter' => $this->detailLetter($letter, $completedAt),
            'initial_route' => [
                'target_position' => $this->position($route->recipientPosition),
                'routed_by' => $this->routeActor($route),
                'routed_at' => $route->routed_at->toISOString(),
            ],
            'sekda_handoff' => $sekdaHandoff,
            'branches' => $branches,
            'progress' => $this->branchProgress($terminalBranches),
            'visibility_note' => 'Detail hanya memuat cabang dan catatan yang berada dalam cakupan jabatan aktif Anda.',
        ];
    }

    /**
     * @param  list<string>  $participantCodes
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}|null  $filters
     * @return array<string, mixed>
     */
    private function letter(IncomingLetter $letter, array $participantCodes, ?array $filters): array
    {
        $total = (int) $letter->getAttribute('report_branch_total');
        $completed = (int) $letter->getAttribute('report_branch_completed');
        $processingStartedAt = $this->timestampString($letter->getAttribute('processing_started_at'));
        $completedAt = $letter->status->value === 'COMPLETED'
            ? $this->timestampString($letter->getAttribute('report_completed_at'))
            : null;
        $turnaroundHours = $completedAt === null
            ? null
            : round($letter->received_at->diffInSeconds(CarbonImmutable::parse($completedAt), false) / 3600, 1);

        return [
            'reference' => (string) $letter->getKey(),
            'agenda_number' => $letter->agenda_number,
            'subject' => $letter->subject,
            'sender_organization_name' => $letter->senderOrganization->name,
            'source' => $letter->submission->source->value,
            'status' => $letter->status->value,
            'received_at' => $letter->received_at->toISOString(),
            'processing_started_at' => $processingStartedAt,
            'completed_at' => $completedAt,
            'turnaround_hours' => $turnaroundHours,
            'participant_position_codes' => $participantCodes,
            'branch_progress' => [
                'total' => $total,
                'pending' => (int) $letter->getAttribute('report_branch_pending'),
                'in_progress' => (int) $letter->getAttribute('report_branch_in_progress'),
                'completed' => $completed,
                'percent_complete' => $total === 0 ? 0 : (int) floor(($completed / $total) * 100),
            ],
            'links' => [
                'detail' => route('back-office.reports.show', [
                    'incomingLetter' => $letter,
                    ...($filters ?? []),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function assistantBranch(
        DispositionRecipient $recipient,
        Disposition $firstDisposition,
        int $expectedCreatorPositionId,
    ): array {
        if ($recipient->received_at === null) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        if ($recipient->childDispositions->count() > 1) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $childDisposition = $recipient->childDispositions->first();
        $children = $childDisposition instanceof Disposition
            ? array_values($childDisposition->recipients
                ->map(fn (DispositionRecipient $branch): array => $this->sectionBranch(
                    $branch,
                    $childDisposition,
                    (int) $recipient->recipient_position_id,
                ))
                ->all())
            : [];
        $assistantActivityAt = $childDisposition instanceof Disposition
            ? $childDisposition->created_at
            : $recipient->received_at;

        return [
            'reference' => 'assistant-recipient-'.$recipient->getKey(),
            'recipient_position' => $this->position($recipient->recipientPosition),
            'status' => $recipient->status->value,
            'received_at' => $recipient->received_at->toISOString(),
            'forwarded_at' => $childDisposition?->created_at->toISOString(),
            'instructions' => $this->instructions($firstDisposition->instructionLabels),
            'instruction_note' => $firstDisposition->instruction_note,
            'disposed_by' => $this->dispositionActor($firstDisposition, $expectedCreatorPositionId),
            'disposed_at' => $firstDisposition->created_at->toISOString(),
            'children' => $children,
            'attention' => $children === []
                ? $this->attention($recipient->status, $assistantActivityAt)
                : $this->childrenAttention($children),
        ];
    }

    /** @return array<string, mixed> */
    private function sectionBranch(
        DispositionRecipient $recipient,
        Disposition $disposition,
        int $expectedCreatorPositionId,
    ): array {
        if ($recipient->received_at === null) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $this->ensureLifecycleIsConsistent($recipient);
        $lastActivityAt = $this->lastActivityAt($recipient);

        return [
            'reference' => 'section-recipient-'.$recipient->getKey(),
            'recipient_position' => $this->position($recipient->recipientPosition),
            'status' => $recipient->status->value,
            'received_at' => $recipient->received_at->toISOString(),
            'started_at' => $recipient->started_at?->toISOString(),
            'completed_at' => $recipient->completed_at?->toISOString(),
            'turnaround_hours' => $recipient->completed_at === null
                ? null
                : round($recipient->received_at->diffInSeconds($recipient->completed_at, false) / 3600, 1),
            'instructions' => $this->instructions($disposition->instructionLabels),
            'instruction_note' => $disposition->instruction_note,
            'disposed_by' => $this->dispositionActor($disposition, $expectedCreatorPositionId),
            'disposed_at' => $disposition->created_at->toISOString(),
            'follow_ups' => $recipient->followUps
                ->map(fn (DispositionFollowUp $followUp): array => [
                    'note' => $followUp->note,
                    'created_at' => $followUp->created_at->toISOString(),
                    'created_by' => $this->historicalActor(
                        $followUp->createdBy,
                        $followUp->createdByPositionAssignment,
                        $recipient->recipient_position_id,
                    ),
                ])
                ->values()
                ->all(),
            'completion_note' => $recipient->completion_note,
            'completed_by' => $recipient->completedBy instanceof User
                && $recipient->completedByPositionAssignment instanceof PositionAssignment
                    ? $this->historicalActor(
                        $recipient->completedBy,
                        $recipient->completedByPositionAssignment,
                        $recipient->recipient_position_id,
                    )
                    : null,
            'attention' => $this->attention($recipient->status, $lastActivityAt),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $branches
     * @return array{total: int, pending: int, in_progress: int, completed: int, percent_complete: int}
     */
    private function branchProgress(array $branches): array
    {
        $total = count($branches);
        $pending = count(array_filter($branches, fn (array $branch): bool => $branch['status'] === 'PENDING'));
        $inProgress = count(array_filter($branches, fn (array $branch): bool => $branch['status'] === 'IN_PROGRESS'));
        $completed = count(array_filter($branches, fn (array $branch): bool => $branch['status'] === 'COMPLETED'));

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'percent_complete' => $total === 0 ? 0 : (int) floor(($completed / $total) * 100),
        ];
    }

    /** @return array<string, mixed> */
    private function detailLetter(IncomingLetter $letter, ?string $completedAt): array
    {
        return [
            'reference' => (string) $letter->getKey(),
            'agenda_number' => $letter->agenda_number,
            'subject' => $letter->subject,
            'sender_organization_name' => $letter->senderOrganization->name,
            'external_letter_number' => $letter->external_letter_number,
            'source' => $letter->submission->source->value,
            'status' => $letter->status->value,
            'received_at' => $letter->received_at->toISOString(),
            'completed_at' => $completedAt,
        ];
    }

    /** @return array{code: string, name: string, unit_name: string|null, official_name: string|null} */
    private function position(Position $position): array
    {
        $assignment = $position->activeAssignment;

        return [
            'code' => $position->code,
            'name' => $position->name,
            'unit_name' => $position->organizationalUnit?->name,
            'official_name' => $assignment instanceof PositionAssignment
                ? $assignment->user->name
                : null,
        ];
    }

    /**
     * @param  Collection<int, InstructionLabel>  $labels
     * @return list<array{code: string, name: string}>
     */
    private function instructions(Collection $labels): array
    {
        return array_values($labels->map(fn (InstructionLabel $label): array => [
            'code' => $label->code,
            'name' => $label->name,
        ])->all());
    }

    /** @return array{name: string, position: string, unit: string|null} */
    private function dispositionActor(Disposition $disposition, ?int $expectedPositionId): array
    {
        return $this->historicalActor(
            $disposition->createdBy,
            $disposition->createdByPositionAssignment,
            $expectedPositionId,
        );
    }

    /** @return array{name: string, position: string, unit: string|null} */
    private function routeActor(LetterRoute $route): array
    {
        $assignment = $route->routedByPositionAssignment;

        if (! $assignment instanceof PositionAssignment) {
            return [
                'name' => $route->routedBy->name,
                'position' => 'Posisi historis tidak tersedia',
                'unit' => null,
            ];
        }

        return $this->historicalActor($route->routedBy, $assignment);
    }

    /** @return array{name: string, position: string, unit: string|null} */
    private function historicalActor(User $user, PositionAssignment $assignment, ?int $expectedPositionId = null): array
    {
        if ((int) $assignment->user_id !== (int) $user->getKey()
            || ($expectedPositionId !== null && (int) $assignment->position_id !== $expectedPositionId)) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        return [
            'name' => $user->name,
            'position' => $assignment->position->name,
            'unit' => $assignment->position->organizationalUnit?->name,
        ];
    }

    private function ensureLifecycleIsConsistent(DispositionRecipient $recipient): void
    {
        $hasCompletionActor = $recipient->completedBy instanceof User
            && $recipient->completedByPositionAssignment instanceof PositionAssignment
            && (int) $recipient->completedByPositionAssignment->user_id === (int) $recipient->completedBy->getKey()
            && (int) $recipient->completedByPositionAssignment->position_id === (int) $recipient->recipient_position_id;
        $hasCompletion = $recipient->completed_at !== null
            && $hasCompletionActor
            && $recipient->completed_at->greaterThanOrEqualTo($recipient->received_at)
            && ($recipient->started_at === null
                || $recipient->completed_at->greaterThanOrEqualTo($recipient->started_at))
            && is_string($recipient->completion_note)
            && trim($recipient->completion_note) === $recipient->completion_note
            && mb_strlen($recipient->completion_note) >= 10
            && mb_strlen($recipient->completion_note) <= 2000;
        $hasNoCompletion = $recipient->completed_at === null
            && $recipient->completed_by_user_id === null
            && $recipient->completed_by_position_assignment_id === null
            && $recipient->completion_note === null;
        $valid = match ($recipient->status) {
            DispositionRecipientStatus::Pending => $recipient->started_at === null && $hasNoCompletion,
            DispositionRecipientStatus::InProgress => $recipient->started_at !== null
                && $recipient->started_at->greaterThanOrEqualTo($recipient->received_at)
                && $hasNoCompletion,
            DispositionRecipientStatus::Completed => $hasCompletion,
        };

        if (! $valid) {
            throw DispositionStateConflict::inconsistentGraph();
        }
    }

    /** @return array{needs_attention: bool, idle_hours: int|null, reason: string|null} */
    private function attention(DispositionRecipientStatus $status, ?CarbonInterface $lastActivityAt): array
    {
        $idleHours = $status !== DispositionRecipientStatus::Completed && $lastActivityAt instanceof CarbonInterface
            ? (int) floor($lastActivityAt->diffInSeconds(now()) / 3600)
            : null;
        $needsAttention = $idleHours !== null && $idleHours >= 48;

        return [
            'needs_attention' => $needsAttention,
            'idle_hours' => $needsAttention ? $idleHours : null,
            'reason' => $needsAttention
                ? "Cabang belum memiliki aktivitas selama {$idleHours} jam."
                : null,
        ];
    }

    private function lastActivityAt(DispositionRecipient $recipient): ?CarbonInterface
    {
        return collect([
            $recipient->received_at,
            $recipient->started_at,
            $recipient->completed_at,
            $recipient->followUps->max('created_at'),
        ])->filter(fn (mixed $timestamp): bool => $timestamp instanceof CarbonInterface)
            ->sortByDesc(fn (CarbonInterface $timestamp): int => $timestamp->getTimestamp())
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array{needs_attention: bool, idle_hours: int|null, reason: string|null}
     */
    private function childrenAttention(array $children): array
    {
        $idleHours = collect($children)
            ->filter(fn (array $child): bool => $child['attention']['needs_attention'] === true)
            ->pluck('attention.idle_hours')
            ->filter(fn (mixed $hours): bool => is_int($hours))
            ->max();

        return [
            'needs_attention' => is_int($idleHours),
            'idle_hours' => is_int($idleHours) ? $idleHours : null,
            'reason' => is_int($idleHours)
                ? "Terdapat cabang turunan tanpa aktivitas selama {$idleHours} jam."
                : null,
        ];
    }

    private function timestampString(mixed $value): ?string
    {
        return is_string($value) ? CarbonImmutable::parse($value)->toISOString() : null;
    }

    /**
     * @param  Collection<int, IncomingLetter>  $letters
     * @return array<int, list<string>>
     */
    private function participantPositionCodes(Collection $letters, User $user): array
    {
        $scope = $this->scopeResolver->resolve($user);
        $letterIds = $letters
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($scope === null || $letterIds === []) {
            return [];
        }

        $routeCodes = DB::table('letter_routes')
            ->join('positions', 'positions.id', '=', 'letter_routes.recipient_position_id')
            ->whereIn('letter_routes.incoming_letter_id', $letterIds)
            ->get(['letter_routes.incoming_letter_id', 'positions.code']);
        $recipientQuery = DB::table('disposition_recipients as report_visible_recipients')
            ->join('dispositions as report_visible_dispositions', 'report_visible_dispositions.id', '=', 'report_visible_recipients.disposition_id')
            ->join('positions as report_visible_positions', 'report_visible_positions.id', '=', 'report_visible_recipients.recipient_position_id')
            ->leftJoin('disposition_recipients as report_parent_recipients', 'report_parent_recipients.id', '=', 'report_visible_dispositions.parent_recipient_id')
            ->whereIn('report_visible_dispositions.incoming_letter_id', $letterIds);

        if (! $scope->hasGlobalDetails) {
            $recipientQuery->where(function ($visible) use ($scope): void {
                if ($scope->assistantPositionIds !== []) {
                    $visible
                        ->whereIn('report_visible_recipients.recipient_position_id', $scope->assistantPositionIds)
                        ->orWhereIn('report_parent_recipients.recipient_position_id', $scope->assistantPositionIds);
                }

                if ($scope->sectionHeadPositionIds !== []) {
                    $visible->orWhereIn('report_visible_recipients.recipient_position_id', $scope->sectionHeadPositionIds);
                }
            });
        }

        $recipientCodes = $recipientQuery->get([
            'report_visible_dispositions.incoming_letter_id',
            'report_visible_positions.code',
        ]);

        $result = [];

        foreach ($routeCodes->concat($recipientCodes) as $row) {
            $letterId = (int) $row->incoming_letter_id;
            $code = (string) $row->code;
            $result[$letterId] ??= [];

            if (! in_array($code, $result[$letterId], true)) {
                $result[$letterId][] = $code;
            }
        }

        return $result;
    }
}
