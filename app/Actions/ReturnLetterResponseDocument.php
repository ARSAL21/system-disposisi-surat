<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\LetterResponseReviewDecision;
use App\Exceptions\LetterResponseStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterResponsePositionAssignmentResolver;
use App\Services\LetterResponseReviewerPositionResolver;
use Illuminate\Support\Facades\DB;

final class ReturnLetterResponseDocument
{
    public function __construct(
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
        private readonly LetterResponseReviewerPositionResolver $reviewerPositionResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        LetterResponseDossier $dossier,
        LetterResponseDocument $document,
        string $reason,
    ): LetterResponseReview {
        return DB::transaction(function () use ($actor, $dossier, $document, $reason): LetterResponseReview {
            $letter = IncomingLetter::query()->whereKey($dossier->incoming_letter_id)->lockForUpdate()->firstOrFail();
            $lockedDossier = LetterResponseDossier::query()->whereKey($dossier->getKey())->lockForUpdate()->firstOrFail();
            $lockedDocument = LetterResponseDocument::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedDossier->status !== LetterResponseDossierStatus::Open
                || (int) $lockedDossier->incoming_letter_id !== (int) $letter->getKey()
                || (int) $lockedDocument->letter_response_dossier_id !== (int) $lockedDossier->getKey()) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $reviewerPositionId = $this->reviewerPositionResolver->resolve($lockedDossier, $lockedDocument);
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $reviewerPositionId);
            $version = LetterResponseDocumentVersion::query()
                ->where('letter_response_document_id', $lockedDocument->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();

            if (LetterResponseReview::query()->where('document_version_id', $version->getKey())->exists()) {
                throw LetterResponseStateConflict::staleDossier();
            }

            if (OutgoingLetter::query()->where('source_document_version_id', $version->getKey())->exists()) {
                throw LetterResponseStateConflict::documentAlreadyMandated();
            }

            if (DB::table('letter_response_document_sources')
                ->where('source_version_id', $version->getKey())
                ->exists()) {
                throw LetterResponseStateConflict::documentAlreadyUsed();
            }

            $review = new LetterResponseReview;
            $review->letter_response_dossier_id = $lockedDossier->getKey();
            $review->document_version_id = $version->getKey();
            $review->decision = LetterResponseReviewDecision::Returned;
            $review->reason = $reason;
            $review->decided_by_user_id = $lockedActor->getKey();
            $review->decided_by_position_assignment_id = $assignment->getKey();
            $review->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::LetterResponseDocumentReturned,
                subjectType: 'incoming_letter',
                subjectId: $letter->getKey(),
                newValues: [
                    'dossier_id' => $lockedDossier->getKey(),
                    'document_version_id' => $version->getKey(),
                    'decision' => LetterResponseReviewDecision::Returned->value,
                ],
                actorPositionAssignment: $assignment,
            );

            return $review;
        }, attempts: 3);
    }
}
