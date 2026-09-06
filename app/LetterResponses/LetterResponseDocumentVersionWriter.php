<?php

namespace App\LetterResponses;

use App\Actions\RecordAudit;
use App\Enums\AuditAction;
use App\Enums\LetterResponseDocumentKind;
use App\Exceptions\LetterResponseStateConflict;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LetterResponseDocumentVersionWriter
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param array<int, int> $sourceVersionIds */
    public function write(
        LetterResponseDossier $dossier,
        LetterResponseDocumentKind $kind,
        int $ownerPositionId,
        ?int $sourceRecipientId,
        User $actor,
        PositionAssignment $assignment,
        StoredLetterResponseDocument $stored,
        ?string $revisionNote = null,
        array $sourceVersionIds = [],
    ): LetterResponseDocumentVersion {
        $document = LetterResponseDocument::query()
            ->where('letter_response_dossier_id', $dossier->getKey())
            ->where('kind', $kind->value)
            ->where('owner_position_id', $ownerPositionId)
            ->lockForUpdate()
            ->first();

        if (! $document instanceof LetterResponseDocument) {
            $document = new LetterResponseDocument;
            $document->letter_response_dossier_id = $dossier->getKey();
            $document->kind = $kind;
            $document->owner_position_id = $ownerPositionId;
            $document->source_recipient_id = $sourceRecipientId;
            $document->created_by_user_id = $actor->getKey();
            $document->created_by_position_assignment_id = $assignment->getKey();
            $document->save();
        }

        if ((int) $document->letter_response_dossier_id !== (int) $dossier->getKey()
            || $document->kind !== $kind
            || (int) $document->owner_position_id !== $ownerPositionId
            || $document->source_recipient_id !== $sourceRecipientId) {
            throw LetterResponseStateConflict::invalidDocument();
        }

        $latest = LetterResponseDocumentVersion::query()
            ->where('letter_response_document_id', $document->getKey())
            ->orderByDesc('version_number')
            ->lockForUpdate()
            ->first();

        if (LetterResponseDocumentVersion::query()
            ->where('letter_response_document_id', $document->getKey())
            ->where('sha256', $stored->sha256)
            ->exists()) {
            throw ValidationException::withMessages([
                'document' => 'Berkas identik dengan versi bahan balasan yang sudah tersimpan.',
            ]);
        }

        $version = new LetterResponseDocumentVersion;
        $version->letter_response_document_id = $document->getKey();
        $version->version_number = (int) data_get($latest, 'version_number', 0) + 1;
        $version->replaces_version_id = $latest?->getKey();
        $version->storage_disk = $stored->disk;
        $version->storage_path = $stored->path;
        $version->original_filename = $stored->originalFilename;
        $version->mime_type = $stored->mimeType;
        $version->size_bytes = $stored->sizeBytes;
        $version->sha256 = $stored->sha256;
        $version->revision_note = $revisionNote;
        $version->uploaded_by_user_id = $actor->getKey();
        $version->uploaded_by_position_assignment_id = $assignment->getKey();
        $version->save();

        foreach (array_values(array_unique($sourceVersionIds)) as $sourceVersionId) {
            DB::table('letter_response_document_sources')->insert([
                'target_version_id' => $version->getKey(),
                'source_version_id' => $sourceVersionId,
                'created_at' => now(),
            ]);
        }

        $this->recordAudit->execute(
            actor: $actor,
            action: AuditAction::LetterResponseDocumentVersionCreated,
            subjectType: 'incoming_letter',
            subjectId: $dossier->incoming_letter_id,
            newValues: [
                'dossier_id' => $dossier->getKey(),
                'document_kind' => $kind->value,
                'owner_position_id' => $ownerPositionId,
                'version_number' => $version->version_number,
                'sha256' => $version->sha256,
                'size_bytes' => $version->size_bytes,
            ],
            actorPositionAssignment: $assignment,
        );

        return $version;
    }
}
