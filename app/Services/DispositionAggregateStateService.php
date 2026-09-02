<?php

namespace App\Services;

use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use Illuminate\Database\Eloquent\Collection;

final class DispositionAggregateStateService
{
    /**
     * @param  Collection<int, DispositionRecipient>  $terminalBranches
     */
    public function completeLetterWhenAllBranchesComplete(
        IncomingLetter $letter,
        Collection $terminalBranches,
    ): bool {
        if ($terminalBranches->isEmpty() || $letter->status !== IncomingLetterStatus::InProgress) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        if ($terminalBranches->contains(
            fn (DispositionRecipient $branch): bool => ! in_array($branch->status, [
                DispositionRecipientStatus::Pending,
                DispositionRecipientStatus::InProgress,
                DispositionRecipientStatus::Completed,
            ], true),
        )) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        if ($terminalBranches->contains(
            fn (DispositionRecipient $branch): bool => $branch->status !== DispositionRecipientStatus::Completed,
        )) {
            return false;
        }

        $letter->status = IncomingLetterStatus::Completed;
        $letter->save();

        return true;
    }
}
