<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingCopyRecipient;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CreateStandaloneOutgoingCorrectionDraft
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, string $reason): StandaloneOutgoingDraft
    {
        $sourceLetter = OutgoingLetter::query()
            ->with('standaloneDraft.currentDocumentVersion')
            ->whereKey($target->getKey())
            ->firstOrFail();
        $sourceDraft = $sourceLetter->standaloneDraft;
        $sourceVersion = $sourceDraft?->currentDocumentVersion;
        if ($sourceLetter->origin !== OutgoingLetterOrigin::Standalone
            || $sourceLetter->status !== OutgoingLetterStatus::Delivered
            || ! $sourceDraft instanceof StandaloneOutgoingDraft
            || ! $sourceVersion instanceof StandaloneOutgoingDocumentVersion) {
            throw OutgoingLetterStateConflict::stale();
        }

        $publicId = (string) Str::ulid();
        $stored = $this->storage->copy($sourceVersion, $sourceDraft, $publicId);

        try {
            return DB::transaction(function () use ($actor, $target, $reason, $publicId, $stored, $sourceVersion): StandaloneOutgoingDraft {
                ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
                $currentSource = StandaloneOutgoingDocumentVersion::query()
                    ->where('standalone_outgoing_draft_id', $draft->getKey())
                    ->orderByDesc('version_number')->lockForUpdate()->firstOrFail();
                $this->storage->validate($draft, $currentSource);
                if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                    || $outgoing->status !== OutgoingLetterStatus::Delivered
                    || (int) $currentSource->getKey() !== (int) $sourceVersion->getKey()
                    || ! hash_equals($currentSource->sha256, $stored->sha256)) {
                    throw OutgoingLetterStateConflict::stale();
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $isStaffOwner = (int) $draft->created_by_user_id === (int) $lockedActor->getKey()
                    && $this->assignmentResolver->hasStaffAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
                $isSectionHead = $this->assignmentResolver->hasSectionHeadAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
                if (! $isStaffOwner && ! $isSectionHead) {
                    throw OutgoingLetterStateConflict::stale();
                }
                $assignment = $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($lockedActor, $draft->organizational_unit_id);

                $correction = new StandaloneOutgoingDraft;
                $correction->public_id = $publicId;
                $correction->organizational_unit_id = $draft->organizational_unit_id;
                $correction->outgoing_letter_template_version_id = $draft->outgoing_letter_template_version_id;
                $correction->recipient_name = $draft->recipient_name;
                $correction->recipient_organization = $draft->recipient_organization;
                $correction->recipient_position = $draft->recipient_position;
                $correction->recipient_address = $draft->recipient_address;
                $correction->recipient_email = $draft->recipient_email;
                $correction->subject = $draft->subject;
                $correction->summary = $draft->summary;
                $correction->status = StandaloneOutgoingDraftStatus::Draft;
                $correction->created_by_user_id = $lockedActor->getKey();
                $correction->created_by_position_assignment_id = $assignment->getKey();
                $correction->corrects_outgoing_letter_id = $outgoing->getKey();
                $correction->correction_reason = trim($reason);
                $correction->save();

                foreach ($draft->copyRecipients()->lockForUpdate()->get() as $copy) {
                    $newCopy = new StandaloneOutgoingCopyRecipient;
                    $newCopy->standalone_outgoing_draft_id = $correction->getKey();
                    $newCopy->position_id = $copy->position_id;
                    $newCopy->save();
                }

                $version = new StandaloneOutgoingDocumentVersion;
                $version->standalone_outgoing_draft_id = $correction->getKey();
                $version->version_number = 1;
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->revision_note = 'Salinan awal untuk surat koreksi.';
                $version->uploaded_by_user_id = $lockedActor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $this->audit->execute(
                    actor: $lockedActor,
                    action: AuditAction::StandaloneOutgoingCorrectionDraftCreated,
                    subjectType: 'standalone_outgoing_draft',
                    subjectId: $correction->getKey(),
                    newValues: ['corrects_outgoing_letter_id' => $outgoing->getKey(), 'document_version' => 1],
                    actorPositionAssignment: $assignment,
                );

                return $correction;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }
}
