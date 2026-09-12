<?php

namespace App\Services;

use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingDraft;

/**
 * Locks the mutable records of a standalone outgoing letter in the one
 * canonical order used by its publication lifecycle.
 *
 * The draft is deliberately locked before the published letter. Review,
 * approval, delivery, and correction can then safely acquire subsequent
 * document and actor-assignment locks without creating an inverse lock order.
 */
final class StandaloneOutgoingLetterLockService
{
    /**
     * @return array{draft: StandaloneOutgoingDraft, outgoing: OutgoingLetter}
     */
    public function lock(OutgoingLetter $target): array
    {
        $draftId = (int) $target->standalone_outgoing_draft_id;
        if ($draftId < 1) {
            throw StandaloneOutgoingStateConflict::stale();
        }

        $draft = StandaloneOutgoingDraft::query()
            ->whereKey($draftId)
            ->lockForUpdate()
            ->firstOrFail();

        $outgoing = OutgoingLetter::query()
            ->whereKey($target->getKey())
            ->where('standalone_outgoing_draft_id', $draft->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return compact('draft', 'outgoing');
    }
}
