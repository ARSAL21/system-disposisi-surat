<?php

namespace App\Dispositions;

use App\Enums\AccountType;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\InstructionLabel;
use App\Models\Position;
use App\Models\PositionAssignment;
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
        $recipient = $disposition->recipients->first();

        if (! $recipient instanceof DispositionRecipient || $disposition->recipients->count() !== 1) {
            throw DispositionStateConflict::staleSource();
        }

        return [
            'status' => $recipient->status->value,
            'recipient_position' => $this->position(
                $recipient->recipientPosition,
                OrganizationCatalog::ASSISTANT_LEVEL,
            ),
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

        return [
            'id' => (int) $position->getKey(),
            'code' => $position->code,
            'name' => $position->name,
            'level_code' => $levelCode,
            'unit_name' => $position->organizationalUnit?->name,
            'holder_name' => $isAvailable ? $holder->name : null,
            'is_available' => $isAvailable,
        ];
    }
}
