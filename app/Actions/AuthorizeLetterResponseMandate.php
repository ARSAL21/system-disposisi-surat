<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDocumentKind;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\LetterResponseStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\LetterResponsePositionAssignmentResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class AuthorizeLetterResponseMandate
{
    public function __construct(
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        LetterResponseDossier $dossier,
        string $sourceVersionPublicId,
        string $signatoryPositionCode,
        string $subject,
    ): OutgoingLetter {
        return DB::transaction(function () use ($actor, $dossier, $sourceVersionPublicId, $signatoryPositionCode, $subject): OutgoingLetter {
            $letter = IncomingLetter::query()->whereKey($dossier->incoming_letter_id)->lockForUpdate()->firstOrFail();
            $lockedDossier = LetterResponseDossier::query()->whereKey($dossier->getKey())->lockForUpdate()->firstOrFail();

            if ($letter->status !== IncomingLetterStatus::Completed
                || $lockedDossier->status !== LetterResponseDossierStatus::Open
                || (int) $lockedDossier->incoming_letter_id !== (int) $letter->getKey()) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $executivePositionId = (int) $letter->routes()->orderBy('id')->value('recipient_position_id');

            if ($executivePositionId < 1) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $executivePositionId);
            $source = LetterResponseDocumentVersion::query()
                ->with('document')
                ->where('public_id', $sourceVersionPublicId)
                ->lockForUpdate()
                ->firstOrFail();

            $latestId = LetterResponseDocumentVersion::query()
                ->where('letter_response_document_id', $source->letter_response_document_id)
                ->orderByDesc('version_number')
                ->value('id');

            if ((int) $source->document->letter_response_dossier_id !== (int) $lockedDossier->getKey()
                || ! in_array($source->document->kind, [
                    LetterResponseDocumentKind::AssistantProposal,
                    LetterResponseDocumentKind::ExecutiveConsolidation,
                ], true)
                || (int) $latestId !== (int) $source->getKey()
                || LetterResponseReview::query()->where('document_version_id', $source->getKey())->exists()) {
                throw LetterResponseStateConflict::invalidDocument();
            }

            $signatory = Position::query()
                ->where('code', $signatoryPositionCode)
                ->where('is_active', true)
                ->whereHas('activeAssignment.user', fn (Builder $user): Builder => $user
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at'))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isEligibleSignatory($letter, $signatory, $executivePositionId)) {
                throw LetterResponseStateConflict::invalidSignatory();
            }

            $mandate = new OutgoingLetter;
            $mandate->incoming_letter_id = $letter->getKey();
            $mandate->letter_response_dossier_id = $lockedDossier->getKey();
            $mandate->source_document_version_id = $source->getKey();
            $mandate->signatory_position_id = $signatory->getKey();
            $mandate->subject = $subject;
            $mandate->status = OutgoingLetterStatus::Authorized;
            $mandate->authorized_by_user_id = $lockedActor->getKey();
            $mandate->authorized_by_position_assignment_id = $assignment->getKey();
            $mandate->authorized_at = Date::now();
            $mandate->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::LetterResponseMandateAuthorized,
                subjectType: 'incoming_letter',
                subjectId: $letter->getKey(),
                newValues: [
                    'incoming_letter_id' => $letter->getKey(),
                    'dossier_id' => $lockedDossier->getKey(),
                    'source_document_version_id' => $source->getKey(),
                    'signatory_position_id' => $signatory->getKey(),
                    'status' => OutgoingLetterStatus::Authorized->value,
                ],
                actorPositionAssignment: $assignment,
            );

            return $mandate;
        }, attempts: 3);
    }

    private function isEligibleSignatory(IncomingLetter $letter, Position $position, int $executivePositionId): bool
    {
        if ((int) $position->getKey() === $executivePositionId
            && $position->positionLevel()->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)->exists()) {
            return true;
        }

        return $position->positionLevel()->where('code', OrganizationCatalog::ASSISTANT_LEVEL)->exists()
            && $letter->dispositions()
                ->whereNotNull('source_route_id')
                ->whereHas('recipients', fn (Builder $recipient): Builder => $recipient
                    ->where('recipient_position_id', $position->getKey()))
                ->exists();
    }
}
