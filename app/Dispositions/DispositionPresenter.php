<?php

namespace App\Dispositions;

use App\Enums\AccountType;
use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\InstructionLabel;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Routing\LetterRoutingPresenter;
use Illuminate\Support\Collection;

final class DispositionPresenter
{
    public function __construct(
        private readonly LetterRoutingPresenter $letterPresenter,
    ) {}

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<array<string, mixed>>
     */
    public function assistantPositions(Collection $positions): array
    {
        return array_values($positions
            ->map(fn (Position $position): array => $this->position(
                $position,
                OrganizationCatalog::ASSISTANT_LEVEL,
            ))
            ->all());
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<array<string, mixed>>
     */
    public function sectionHeadPositions(Collection $positions): array
    {
        return array_values($positions
            ->map(fn (Position $position): array => $this->position(
                $position,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
            ))
            ->all());
    }

    /**
     * @param  Collection<int, InstructionLabel>  $labels
     * @return list<array{id: int, code: string, name: string, description: string|null}>
     */
    public function instructionOptions(Collection $labels): array
    {
        return array_values($labels->map(static fn (InstructionLabel $label): array => [
            'id' => (int) $label->getKey(),
            'code' => $label->code,
            'name' => $label->name,
            'description' => $label->description,
        ])->all());
    }

    /** @return array<string, mixed> */
    public function firstDisposition(Disposition $disposition): array
    {
        if ($disposition->recipients->isEmpty() || $disposition->recipients->count() > 3) {
            throw DispositionStateConflict::staleSource();
        }

        $recipientLevel = $disposition->recipients->first()->recipientPosition->positionLevel->code;
        $isMayorToSekda = $disposition->recipients->count() === 1
            && $recipientLevel === OrganizationCatalog::REGIONAL_SECRETARY_LEVEL;

        if (! $isMayorToSekda && $recipientLevel !== OrganizationCatalog::ASSISTANT_LEVEL) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        return [
            'recipients' => $disposition->recipients
                ->map(fn (DispositionRecipient $recipient): array => [
                    'status' => $recipient->status->value,
                    'recipient_position' => $this->position(
                        $recipient->recipientPosition,
                        $isMayorToSekda
                            ? OrganizationCatalog::REGIONAL_SECRETARY_LEVEL
                            : OrganizationCatalog::ASSISTANT_LEVEL,
                    ),
                ])
                ->values()
                ->all(),
            'instructions' => $this->instructionSnapshots($disposition->instructionLabels),
            'instruction_note' => $disposition->instruction_note,
            'disposed_by' => $this->actor($disposition),
            'disposed_at' => $disposition->created_at->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    public function forwardedDisposition(Disposition $disposition): array
    {
        if ($disposition->recipients->isEmpty()) {
            throw DispositionStateConflict::staleSource();
        }

        return [
            'instructions' => $this->instructionSnapshots($disposition->instructionLabels),
            'instruction_note' => $disposition->instruction_note,
            'recipients' => $disposition->recipients
                ->map(function (DispositionRecipient $recipient): array {
                    if ($recipient->received_at === null) {
                        throw DispositionStateConflict::staleSource();
                    }

                    return [
                        'recipient_position' => $this->position(
                            $recipient->recipientPosition,
                            OrganizationCatalog::SECTION_HEAD_LEVEL,
                        ),
                        'status' => $recipient->status->value,
                        'received_at' => $recipient->received_at->toISOString(),
                    ];
                })
                ->values()
                ->all(),
            'disposed_by' => $this->actor($disposition),
            'disposed_at' => $disposition->created_at->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    public function branchLifecycle(DispositionRecipient $recipient): array
    {
        if ($recipient->received_at === null) {
            throw DispositionStateConflict::staleSource();
        }

        $this->ensureBranchLifecycleIsConsistent($recipient);

        return [
            'status' => $recipient->status->value,
            'received_at' => $recipient->received_at->toISOString(),
            'started_at' => $recipient->started_at?->toISOString(),
            'completed_at' => $recipient->completed_at?->toISOString(),
            'completion_note' => $recipient->completion_note,
            'completed_by' => $recipient->completedBy instanceof User
                && $recipient->completedByPositionAssignment instanceof PositionAssignment
                    ? $this->historicalActor(
                        $recipient->completedBy,
                        $recipient->completedByPositionAssignment,
                        $recipient->recipient_position_id,
                    )
                    : null,
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
        ];
    }

    /** @return array<string, mixed> */
    public function assistantBranchMonitor(Disposition $disposition): array
    {
        if ($disposition->recipients->isEmpty()) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $branches = $disposition->recipients
            ->sortBy([
                ['received_at', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn (DispositionRecipient $recipient): array => [
                ...$this->branchLifecycle($recipient),
                'recipient_position' => $this->position(
                    $recipient->recipientPosition,
                    OrganizationCatalog::SECTION_HEAD_LEVEL,
                ),
            ])
            ->values();

        return $this->branchProgress(array_values($branches->all()), true);
    }

    /** @return array<string, int|string> */
    public function executiveBranchProgress(?Disposition $firstDisposition): array
    {
        if (! $firstDisposition instanceof Disposition) {
            return $this->emptyBranchProgress('AWAITING_DECISION');
        }

        $assistantRecipients = $firstDisposition->recipients;
        if ($assistantRecipients->isEmpty() || $assistantRecipients->count() > 3) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        if ($assistantRecipients->count() === 1
            && $assistantRecipients->first()->recipientPosition->positionLevel->code === OrganizationCatalog::REGIONAL_SECRETARY_LEVEL) {
            $sekdaRecipient = $assistantRecipients->firstOrFail();
            $forwardedDisposition = $sekdaRecipient->childDispositions->first();

            if ($sekdaRecipient->childDispositions->count() > 1) {
                throw DispositionStateConflict::inconsistentGraph();
            }

            if (! $forwardedDisposition instanceof Disposition) {
                return $this->emptyBranchProgress('AWAITING_FORWARDING');
            }

            return $this->executiveBranchProgress($forwardedDisposition);
        }

        $branches = [];
        $hasUnforwardedAssistant = false;

        foreach ($assistantRecipients as $assistantRecipient) {
            if ($assistantRecipient->recipientPosition->positionLevel->code !== OrganizationCatalog::ASSISTANT_LEVEL) {
                throw DispositionStateConflict::inconsistentGraph();
            }

            $forwardedDispositions = $assistantRecipient->childDispositions;
            if ($forwardedDispositions->count() > 1) {
                throw DispositionStateConflict::inconsistentGraph();
            }

            $forwardedDisposition = $forwardedDispositions->first();
            if (! $forwardedDisposition instanceof Disposition) {
                $hasUnforwardedAssistant = true;

                continue;
            }

            if ($forwardedDisposition->parent_recipient_id !== $assistantRecipient->getKey()
                || $forwardedDisposition->incoming_letter_id !== $firstDisposition->incoming_letter_id
                || $forwardedDisposition->recipients->isEmpty()) {
                throw DispositionStateConflict::inconsistentGraph();
            }

            foreach ($forwardedDisposition->recipients as $recipient) {
                if ($recipient->recipientPosition->positionLevel->code !== OrganizationCatalog::SECTION_HEAD_LEVEL) {
                    throw DispositionStateConflict::inconsistentGraph();
                }

                $branches[] = ['status' => $recipient->status->value];
            }
        }

        if ($hasUnforwardedAssistant) {
            return $branches === []
                ? $this->emptyBranchProgress('AWAITING_FORWARDING')
                : [
                    'phase' => 'AWAITING_FORWARDING',
                    ...$this->branchCounts($branches),
                ];
        }

        return $this->branchProgress($branches, false);
    }

    /** @return array<string, mixed> */
    public function inboxRecipient(DispositionRecipient $recipient): array
    {
        if ($recipient->received_at === null) {
            throw DispositionStateConflict::staleSource();
        }

        $disposition = $recipient->disposition;
        $letter = $disposition->incomingLetter;
        $presentedLetter = $this->letterPresenter->dispositionInboxLetter($letter, $recipient);

        return [
            'recipient_id' => (int) $recipient->getKey(),
            'letter' => $presentedLetter,
            'sender' => $this->actor($disposition),
            'recipient_position' => $this->position($recipient->recipientPosition),
            'instructions' => $this->instructionSnapshots($disposition->instructionLabels),
            'instruction_note' => $disposition->instruction_note,
            'status' => $recipient->status->value,
            'received_at' => $recipient->received_at->toISOString(),
            'current_document' => $presentedLetter['current_document'],
            'links' => [
                'show' => route('back-office.dispositions.inbox.show', $recipient),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function executiveInboxRecipient(DispositionRecipient $recipient): array
    {
        if ($recipient->received_at === null) {
            throw DispositionStateConflict::staleSource();
        }

        $disposition = $recipient->disposition;
        $letter = $disposition->incomingLetter;
        $presentedLetter = $this->letterPresenter->executiveInboxRecipientLetter($letter, $recipient);

        return [
            'recipient_id' => (int) $recipient->getKey(),
            'letter' => $presentedLetter,
            'sender' => $this->actor($disposition),
            'recipient_position' => $this->position(
                $recipient->recipientPosition,
                OrganizationCatalog::REGIONAL_SECRETARY_LEVEL,
            ),
            'instructions' => $this->instructionSnapshots($disposition->instructionLabels),
            'instruction_note' => $disposition->instruction_note,
            'status' => $recipient->status->value,
            'received_at' => $recipient->received_at->toISOString(),
            'current_document' => $presentedLetter['current_document'],
            'links' => [
                'show' => route('back-office.executive.inbox.recipient.show', $recipient),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function instructionLabel(InstructionLabel $label): array
    {
        return [
            'id' => (int) $label->getKey(),
            'code' => $label->code,
            'name' => $label->name,
            'description' => $label->description,
            'sort_order' => $label->sort_order,
            'is_active' => $label->is_active,
            'created_at' => $label->created_at?->toISOString(),
            'updated_at' => $label->updated_at?->toISOString(),
            'links' => [
                'update' => route('back-office.workflow.instruction-labels.update', $label),
                'status' => route('back-office.workflow.instruction-labels.status', $label),
            ],
        ];
    }

    /** @return array{name: string, position: string, unit: string|null} */
    private function actor(Disposition $disposition): array
    {
        $position = $disposition->createdByPositionAssignment->position;

        return [
            'name' => $disposition->createdBy->name,
            'position' => $position->name,
            'unit' => $position->organizationalUnit?->name,
        ];
    }

    /** @return array{name: string, position: string, unit: string|null} */
    private function historicalActor(
        User $user,
        PositionAssignment $assignment,
        ?int $expectedPositionId = null,
    ): array {
        if ((int) $assignment->user_id !== (int) $user->getKey()
            || ($expectedPositionId !== null
                && (int) $assignment->position_id !== $expectedPositionId)
        ) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $position = $assignment->position;

        return [
            'name' => $user->name,
            'position' => $position->name,
            'unit' => $position->organizationalUnit?->name,
        ];
    }

    private function ensureBranchLifecycleIsConsistent(DispositionRecipient $recipient): void
    {
        $hasHistoricalCompletionActor = $recipient->completedBy instanceof User
            && $recipient->completedByPositionAssignment instanceof PositionAssignment
            && (int) $recipient->completedByPositionAssignment->user_id === (int) $recipient->completedBy->getKey()
            && (int) $recipient->completedByPositionAssignment->position_id === (int) $recipient->recipient_position_id;
        $hasCompletionContext = $recipient->completed_at !== null
            && $recipient->completed_by_user_id !== null
            && $recipient->completed_by_position_assignment_id !== null
            && $hasHistoricalCompletionActor
            && is_string($recipient->completion_note)
            && trim($recipient->completion_note) === $recipient->completion_note
            && mb_strlen($recipient->completion_note) >= 10
            && mb_strlen($recipient->completion_note) <= 2000;
        $hasNoCompletionContext = $recipient->completed_at === null
            && $recipient->completed_by_user_id === null
            && $recipient->completed_by_position_assignment_id === null
            && $recipient->completion_note === null;
        $isConsistent = match ($recipient->status) {
            DispositionRecipientStatus::Pending => $recipient->started_at === null
                && $hasNoCompletionContext
                && $recipient->followUps->isEmpty(),
            DispositionRecipientStatus::InProgress => $recipient->started_at !== null
                && $hasNoCompletionContext,
            DispositionRecipientStatus::Completed => $hasCompletionContext,
        };

        if (! $isConsistent) {
            throw DispositionStateConflict::inconsistentGraph();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $branches
     * @return array<string, mixed>
     */
    private function branchProgress(array $branches, bool $includeBranches): array
    {
        $progress = $this->branchCounts($branches);

        if ($includeBranches) {
            return [...$progress, 'branches' => $branches];
        }

        return [
            'phase' => $progress['completed'] === $progress['total'] ? 'COMPLETED' : 'IN_PROGRESS',
            ...$progress,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $branches
     * @return array{total: int, pending: int, in_progress: int, completed: int, percent_complete: int}
     */
    private function branchCounts(array $branches): array
    {
        $total = count($branches);
        $pending = count(array_filter(
            $branches,
            static fn (array $branch): bool => $branch['status'] === 'PENDING',
        ));
        $inProgress = count(array_filter(
            $branches,
            static fn (array $branch): bool => $branch['status'] === 'IN_PROGRESS',
        ));
        $completed = count(array_filter(
            $branches,
            static fn (array $branch): bool => $branch['status'] === 'COMPLETED',
        ));

        if ($total === 0 || ($pending + $inProgress + $completed) !== $total) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'percent_complete' => (int) floor(($completed / $total) * 100),
        ];
    }

    /** @return array{phase: string, total: int, pending: int, in_progress: int, completed: int, percent_complete: int} */
    private function emptyBranchProgress(string $phase): array
    {
        return [
            'phase' => $phase,
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'percent_complete' => 0,
        ];
    }

    /**
     * @param  Collection<int, InstructionLabel>  $labels
     * @return list<array{code: string, name: string}>
     */
    private function instructionSnapshots(Collection $labels): array
    {
        return array_values($labels->map(static fn (InstructionLabel $label): array => [
            'code' => $label->code,
            'name' => $label->name,
        ])->all());
    }

    /** @return array<string, mixed> */
    private function position(Position $position, ?string $expectedLevelCode = null): array
    {
        $levelCode = $position->positionLevel->code;

        if (($expectedLevelCode !== null && $levelCode !== $expectedLevelCode)
            || ! in_array($levelCode, [
                OrganizationCatalog::MAYOR_LEVEL,
                OrganizationCatalog::REGIONAL_SECRETARY_LEVEL,
                OrganizationCatalog::ASSISTANT_LEVEL,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
            ], true)) {
            throw DispositionStateConflict::staleSource();
        }

        $assignment = $position->activeAssignment;
        $holder = $assignment instanceof PositionAssignment ? $assignment->user : null;
        $hasExactlyOneActiveAssignment = array_key_exists(
            'active_assignments_count',
            $position->getAttributes(),
        )
            ? (int) $position->getAttribute('active_assignments_count') === 1
            : $assignment instanceof PositionAssignment;
        $isAvailable = $position->is_active
            && $hasExactlyOneActiveAssignment
            && $assignment instanceof PositionAssignment
            && $assignment->started_at->lessThanOrEqualTo(now())
            && $holder !== null
            && $holder->account_type === AccountType::InternalAccount
            && $holder->is_active
            && $holder->hasVerifiedEmail();
        $assignedByName = $position->getAttribute('assigned_by_name');

        return [
            'id' => (int) $position->getKey(),
            'code' => $position->code,
            'name' => $position->name,
            'level_code' => $levelCode,
            'unit_name' => $position->organizationalUnit?->name,
            'holder_name' => $isAvailable ? $holder->name : null,
            'is_available' => $isAvailable,
            'assigned_by_name' => is_string($assignedByName) ? $assignedByName : null,
        ];
    }
}
