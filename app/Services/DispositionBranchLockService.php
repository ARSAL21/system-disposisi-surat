<?php

namespace App\Services;

use App\Dispositions\LockedDispositionBranchContext;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

final class DispositionBranchLockService
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
    ) {}

    public function lock(User $actor, DispositionRecipient $requestedBranch): LockedDispositionBranchContext
    {
        $letterId = Disposition::query()
            ->whereKey($requestedBranch->disposition_id)
            ->value('incoming_letter_id');

        if (! is_int($letterId)) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $letter = IncomingLetter::query()
            ->whereKey($letterId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($letter->status !== IncomingLetterStatus::InProgress) {
            throw DispositionStateConflict::staleBranch();
        }

        $terminalBranches = DispositionRecipient::query()
            ->whereHas('disposition', fn (Builder $disposition): Builder => $disposition
                ->where('incoming_letter_id', $letter->getKey()))
            ->whereHas('recipientPosition.positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL))
            ->with([
                'recipientPosition.positionLevel:id,code',
                'disposition:id,incoming_letter_id,parent_recipient_id',
                'disposition.parentRecipient:id,recipient_position_id,status',
                'disposition.parentRecipient.recipientPosition.positionLevel:id,code',
            ])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($terminalBranches->isEmpty()) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        $branch = $terminalBranches->firstWhere('id', $requestedBranch->getKey());

        if (! $branch instanceof DispositionRecipient) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        foreach ($terminalBranches as $terminalBranch) {
            $this->validateTerminalBranch($terminalBranch, (int) $letter->getKey());
        }

        $lockedActor = User::query()
            ->whereKey($actor->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        $assignment = $this->positionAssignmentResolver
            ->lockSectionHeadAssignmentForPosition($lockedActor, $branch->recipient_position_id);

        return new LockedDispositionBranchContext(
            actor: $lockedActor,
            letter: $letter,
            terminalBranches: $terminalBranches,
            branch: $branch,
            actorPositionAssignment: $assignment,
        );
    }

    private function validateTerminalBranch(DispositionRecipient $branch, int $letterId): void
    {
        $disposition = $branch->disposition;
        $parent = $disposition->parentRecipient;

        if ($disposition->incoming_letter_id !== $letterId
            || $disposition->parent_recipient_id === null
            || ! $parent instanceof DispositionRecipient
            || $parent->status !== DispositionRecipientStatus::Completed
            || $parent->recipientPosition->positionLevel->code !== OrganizationCatalog::ASSISTANT_LEVEL
            || $branch->recipientPosition->positionLevel->code !== OrganizationCatalog::SECTION_HEAD_LEVEL
        ) {
            throw DispositionStateConflict::inconsistentGraph();
        }
    }
}
