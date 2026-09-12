<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterElectronicApprovalMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\StandaloneOutgoingSekdaDecisionType;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\OutgoingLetterElectronicApproval;
use App\Models\PositionAssignment;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingSekdaDecision;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingFinalDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use App\Services\StandaloneOutgoingQrStamper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ApproveStandaloneOutgoingWithQr
{
    public function __construct(
        private readonly StandaloneOutgoingQrStamper $stamper,
        private readonly StandaloneOutgoingFinalDocumentStorage $storage,
        private readonly StandaloneOutgoingDocumentStorage $sourceStorage,
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target): OutgoingLetter
    {
        $source = StandaloneOutgoingDocumentVersion::query()
            ->where('standalone_outgoing_draft_id', $target->standalone_outgoing_draft_id)
            ->orderByDesc('version_number')
            ->firstOrFail();
        $template = $source->draft()->with('templateVersion')->firstOrFail()->templateVersion;
        $token = Str::random(64);
        $stored = $this->stamper->stamp(
            $target,
            $source,
            $template,
            route('public.outgoing-letter-verifications.show', ['token' => $token]),
        );

        try {
            return DB::transaction(function () use ($actor, $target, $source, $token, $stored): OutgoingLetter {
                ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
                $currentSource = StandaloneOutgoingDocumentVersion::query()
                    ->where('standalone_outgoing_draft_id', $draft->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->sourceStorage->validate($draft, $currentSource);
                if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                    || $outgoing->status !== OutgoingLetterStatus::SekdaReview
                    || $draft->status !== StandaloneOutgoingDraftStatus::SekdaReview
                    || (int) $currentSource->getKey() !== (int) $source->getKey()
                    || OutgoingLetterElectronicApproval::query()
                        ->where('outgoing_letter_id', $outgoing->getKey())
                        ->where('source_document_sha256', $currentSource->sha256)
                        ->lockForUpdate()
                        ->exists()) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $assignment = $this->assignmentResolver->lockSekdaAssignment($lockedActor);
                $replacedVersionNumber = OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->value('version_number');
                $replacedVersionId = OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->value('id');
                $version = new OutgoingLetterDocumentVersion;
                $version->outgoing_letter_id = $outgoing->getKey();
                $version->version_number = is_numeric($replacedVersionNumber)
                    ? (int) $replacedVersionNumber + 1
                    : 1;
                $version->replaces_version_id = is_numeric($replacedVersionId)
                    ? (int) $replacedVersionId
                    : null;
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->upload_note = 'PDF bernomor disahkan dengan QR oleh Sekda.';
                $version->uploaded_by_user_id = $lockedActor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $approval = new OutgoingLetterElectronicApproval;
                $approval->outgoing_letter_id = $outgoing->getKey();
                $approval->method = OutgoingLetterElectronicApprovalMethod::Qr;
                $approval->source_document_sha256 = $currentSource->sha256;
                $approval->final_document_version_id = $version->getKey();
                $approval->verification_token_hash = hash('sha256', $token);
                $approval->approved_by_user_id = $lockedActor->getKey();
                $approval->approved_by_position_assignment_id = $assignment->getKey();
                $approval->approved_at = now();
                $approval->save();

                $this->decision($outgoing, $lockedActor, $assignment, StandaloneOutgoingSekdaDecisionType::QrApproved, null);
                $outgoing->status = OutgoingLetterStatus::ReadyForDelivery;
                $outgoing->save();
                $draft->status = StandaloneOutgoingDraftStatus::ReadyForDelivery;
                $draft->save();

                $this->audit->execute(
                    actor: $lockedActor,
                    action: AuditAction::StandaloneOutgoingQrApproved,
                    subjectType: 'standalone_outgoing_draft',
                    subjectId: $draft->getKey(),
                    oldValues: ['status' => OutgoingLetterStatus::SekdaReview->value],
                    newValues: ['outgoing_letter_id' => $outgoing->getKey(), 'final_document_version' => $version->version_number],
                    actorPositionAssignment: $assignment,
                );

                return $outgoing;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }

    private function decision(OutgoingLetter $letter, User $actor, PositionAssignment $assignment, StandaloneOutgoingSekdaDecisionType $decision, ?string $note): void
    {
        $entry = new StandaloneOutgoingSekdaDecision;
        $entry->outgoing_letter_id = $letter->getKey();
        $entry->decision = $decision;
        $entry->note = $note;
        $entry->decided_by_user_id = $actor->getKey();
        $entry->decided_by_position_assignment_id = $assignment->getKey();
        $entry->save();
    }
}
