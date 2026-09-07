<?php

namespace App\Services;

use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDossier;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Collection;

final class OutgoingLetterLockService
{
    /**
     * @return array{letter: IncomingLetter, dossier: LetterResponseDossier, outgoing: OutgoingLetter, mandates: Collection<int, OutgoingLetter>}
     */
    public function lock(OutgoingLetter $target): array
    {
        if ($target->incoming_letter_id === null || $target->letter_response_dossier_id === null) {
            throw OutgoingLetterStateConflict::invalidGraph();
        }

        $letter = IncomingLetter::query()
            ->whereKey($target->incoming_letter_id)
            ->lockForUpdate()
            ->firstOrFail();
        $dossier = LetterResponseDossier::query()
            ->whereKey($target->letter_response_dossier_id)
            ->lockForUpdate()
            ->firstOrFail();
        $mandates = OutgoingLetter::query()
            ->where('letter_response_dossier_id', $dossier->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $outgoing = $mandates->firstWhere('id', $target->getKey());

        if (! $outgoing instanceof OutgoingLetter
            || (int) $dossier->incoming_letter_id !== (int) $letter->getKey()
            || (int) $outgoing->incoming_letter_id !== (int) $letter->getKey()) {
            throw OutgoingLetterStateConflict::invalidGraph();
        }

        return compact('letter', 'dossier', 'outgoing', 'mandates');
    }
}
