<?php

namespace App\Services;

use App\Enums\LetterResponseDocumentKind;
use App\Exceptions\LetterResponseStateConflict;
use App\LetterResponses\LetterResponseSekdaPositionResolver;
use App\Models\DispositionRecipient;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDossier;

final class LetterResponseReviewerPositionResolver
{
    public function __construct(private readonly LetterResponseSekdaPositionResolver $sekdaPositionResolver) {}

    public function resolve(LetterResponseDossier $dossier, LetterResponseDocument $document): int
    {
        if ((int) $document->letter_response_dossier_id !== (int) $dossier->getKey()) {
            throw LetterResponseStateConflict::invalidDocument();
        }

        if ($document->kind === LetterResponseDocumentKind::AssistantProposal) {
            return $this->sekdaPositionResolver->lockPositionId($dossier->incomingLetter);
        }

        if ($document->kind === LetterResponseDocumentKind::TechnicalMaterial) {
            $source = $document->sourceRecipient()->with('disposition.parentRecipient')->first();
            $parent = $source?->disposition->parentRecipient;

            if ($source instanceof DispositionRecipient
                && $parent instanceof DispositionRecipient
                && (int) $source->disposition->incoming_letter_id === (int) $dossier->incoming_letter_id) {
                return $parent->recipient_position_id;
            }
        }

        throw LetterResponseStateConflict::invalidDocument();
    }
}
