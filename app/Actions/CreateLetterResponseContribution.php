<?php

namespace App\Actions;

use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDocumentKind;
use App\Enums\LetterResponseDossierStatus;
use App\Exceptions\DispositionStateConflict;
use App\Exceptions\LetterResponseStateConflict;
use App\LetterResponses\LetterResponseDocumentVersionWriter;
use App\LetterResponses\StoredLetterResponseDocument;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\LetterResponseDocumentStorage;
use App\Services\LetterResponsePositionAssignmentResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateLetterResponseContribution
{
    public function __construct(
        private readonly LetterResponseDocumentStorage $storage,
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
        private readonly LetterResponseDocumentVersionWriter $writer,
    ) {}

    public function technicalMaterial(
        User $actor,
        LetterResponseDossier $dossier,
        DispositionRecipient $recipient,
        UploadedFile $file,
        string $note,
    ): LetterResponseDocumentVersion {
        return $this->withStoredFile($file, $dossier, function ($stored) use ($actor, $dossier, $recipient, $note): LetterResponseDocumentVersion {
            return DB::transaction(function () use ($actor, $dossier, $recipient, $note, $stored): LetterResponseDocumentVersion {
                [$lockedDossier, $lockedActor] = $this->lockBase($dossier, $actor);
                $branch = $this->lockRecipient($recipient, $lockedDossier, OrganizationCatalog::SECTION_HEAD_LEVEL);

                if ($branch->status !== DispositionRecipientStatus::Completed) {
                    throw LetterResponseStateConflict::staleDossier();
                }

                $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $branch->recipient_position_id);
                $this->ensureSeriesDoesNotExist($lockedDossier, LetterResponseDocumentKind::TechnicalMaterial, $branch->recipient_position_id);

                return $this->writer->write(
                    $lockedDossier,
                    LetterResponseDocumentKind::TechnicalMaterial,
                    $branch->recipient_position_id,
                    $branch->getKey(),
                    $lockedActor,
                    $assignment,
                    $stored,
                    $note,
                );
            }, attempts: 3);
        });
    }

    public function assistantProposal(
        User $actor,
        LetterResponseDossier $dossier,
        DispositionRecipient $assistantRecipient,
        UploadedFile $file,
        string $note,
    ): LetterResponseDocumentVersion {
        return $this->withStoredFile($file, $dossier, function ($stored) use ($actor, $dossier, $assistantRecipient, $note): LetterResponseDocumentVersion {
            return DB::transaction(function () use ($actor, $dossier, $assistantRecipient, $note, $stored): LetterResponseDocumentVersion {
                [$lockedDossier, $lockedActor] = $this->lockBase($dossier, $actor);
                $assistant = $this->lockRecipient($assistantRecipient, $lockedDossier, OrganizationCatalog::ASSISTANT_LEVEL);
                $childRecipientIds = $this->assertAssistantSubtreeComplete($assistant, $lockedDossier->incoming_letter_id);
                $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $assistant->recipient_position_id);
                $this->ensureSeriesDoesNotExist($lockedDossier, LetterResponseDocumentKind::AssistantProposal, $assistant->recipient_position_id);
                $sourceIds = $this->resolveTechnicalMaterialSources($lockedDossier, $childRecipientIds);

                return $this->writer->write(
                    $lockedDossier,
                    LetterResponseDocumentKind::AssistantProposal,
                    $assistant->recipient_position_id,
                    $assistant->getKey(),
                    $lockedActor,
                    $assignment,
                    $stored,
                    $note,
                    $sourceIds,
                );
            }, attempts: 3);
        });
    }

    /** @param list<string> $sourceVersionPublicIds */
    public function executiveConsolidation(
        User $actor,
        LetterResponseDossier $dossier,
        UploadedFile $file,
        string $note,
        array $sourceVersionPublicIds,
    ): LetterResponseDocumentVersion {
        return $this->withStoredFile($file, $dossier, function ($stored) use ($actor, $dossier, $note, $sourceVersionPublicIds): LetterResponseDocumentVersion {
            return DB::transaction(function () use ($actor, $dossier, $note, $sourceVersionPublicIds, $stored): LetterResponseDocumentVersion {
                [$lockedDossier, $lockedActor, $letter] = $this->lockBase($dossier, $actor);

                if ($letter->status !== IncomingLetterStatus::Completed) {
                    throw LetterResponseStateConflict::staleDossier();
                }

                $executivePositionId = $this->initialExecutivePositionId($letter);
                $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $executivePositionId);
                $this->ensureSeriesDoesNotExist($lockedDossier, LetterResponseDocumentKind::ExecutiveConsolidation, $executivePositionId);
                $sourceIds = $this->resolveConsolidationSources($lockedDossier, $sourceVersionPublicIds);

                return $this->writer->write(
                    $lockedDossier,
                    LetterResponseDocumentKind::ExecutiveConsolidation,
                    $executivePositionId,
                    null,
                    $lockedActor,
                    $assignment,
                    $stored,
                    $note,
                    $sourceIds,
                );
            }, attempts: 3);
        });
    }

    public function revision(
        User $actor,
        LetterResponseDossier $dossier,
        LetterResponseDocument $document,
        UploadedFile $file,
        string $note,
    ): LetterResponseDocumentVersion {
        return $this->withStoredFile($file, $dossier, function ($stored) use ($actor, $dossier, $document, $note): LetterResponseDocumentVersion {
            return DB::transaction(function () use ($actor, $dossier, $document, $note, $stored): LetterResponseDocumentVersion {
                [$lockedDossier, $lockedActor, $letter] = $this->lockBase($dossier, $actor);
                $lockedDocument = LetterResponseDocument::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

                if ((int) $lockedDocument->letter_response_dossier_id !== (int) $lockedDossier->getKey()) {
                    throw LetterResponseStateConflict::invalidDocument();
                }

                $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $lockedDocument->owner_position_id);
                $latest = LetterResponseDocumentVersion::query()
                    ->where('letter_response_document_id', $lockedDocument->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->firstOrFail();

                if (OutgoingLetter::query()
                    ->whereIn('source_document_version_id', LetterResponseDocumentVersion::query()
                        ->select('id')
                        ->where('letter_response_document_id', $lockedDocument->getKey()))
                    ->exists()) {
                    throw LetterResponseStateConflict::documentAlreadyMandated();
                }

                if (DB::table('letter_response_document_sources')
                    ->where('source_version_id', $latest->getKey())
                    ->exists()) {
                    throw LetterResponseStateConflict::documentAlreadyUsed();
                }

                if ($lockedDocument->kind !== LetterResponseDocumentKind::ExecutiveConsolidation
                    && ! LetterResponseReview::query()->where('document_version_id', $latest->getKey())->exists()) {
                    throw LetterResponseStateConflict::staleDossier();
                }

                if ($lockedDocument->kind === LetterResponseDocumentKind::AssistantProposal) {
                    $recipient = $lockedDocument->sourceRecipient;

                    if (! $recipient instanceof DispositionRecipient) {
                        throw LetterResponseStateConflict::invalidDocument();
                    }

                    $this->assertAssistantSubtreeComplete($recipient, $letter->getKey());
                }

                $sourceIds = $latest->sourceVersions()
                    ->pluck('letter_response_document_versions.id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->values()
                    ->all();

                return $this->writer->write(
                    $lockedDossier,
                    $lockedDocument->kind,
                    $lockedDocument->owner_position_id,
                    $lockedDocument->source_recipient_id,
                    $lockedActor,
                    $assignment,
                    $stored,
                    $note,
                    $sourceIds,
                );
            }, attempts: 3);
        });
    }

    /** @return array{LetterResponseDossier, User, IncomingLetter} */
    private function lockBase(LetterResponseDossier $dossier, User $actor): array
    {
        $letter = IncomingLetter::query()
            ->whereKey($dossier->incoming_letter_id)
            ->lockForUpdate()
            ->firstOrFail();
        $lockedDossier = LetterResponseDossier::query()
            ->whereKey($dossier->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $lockedDossier->incoming_letter_id !== (int) $letter->getKey()
            || $lockedDossier->status !== LetterResponseDossierStatus::Open
            || ! in_array($letter->status, [
                IncomingLetterStatus::InProgress,
                IncomingLetterStatus::Completed,
            ], true)) {
            throw LetterResponseStateConflict::staleDossier();
        }

        $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();

        return [$lockedDossier, $lockedActor, $letter];
    }

    private function lockRecipient(
        DispositionRecipient $requested,
        LetterResponseDossier $dossier,
        string $expectedLevel,
    ): DispositionRecipient {
        $recipient = DispositionRecipient::query()
            ->with('recipientPosition.positionLevel:id,code')
            ->whereKey($requested->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($recipient->recipientPosition->positionLevel->code !== $expectedLevel
            || ! $recipient->disposition()->where('incoming_letter_id', $dossier->incoming_letter_id)->exists()) {
            throw LetterResponseStateConflict::invalidDocument();
        }

        return $recipient;
    }

    /** @return array<int, int> */
    private function assertAssistantSubtreeComplete(DispositionRecipient $assistant, int $letterId): array
    {
        $children = DispositionRecipient::query()
            ->whereHas('disposition', fn (Builder $disposition): Builder => $disposition
                ->where('incoming_letter_id', $letterId)
                ->where('parent_recipient_id', $assistant->getKey()))
            ->whereHas('recipientPosition.positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL))
            ->lockForUpdate()
            ->get(['id', 'status']);

        if ($children->isEmpty()
            || $children->contains(fn (DispositionRecipient $recipient): bool => $recipient->status !== DispositionRecipientStatus::Completed)) {
            throw LetterResponseStateConflict::incompleteSubtree();
        }

        return $children
            ->map(fn (DispositionRecipient $recipient): int => (int) $recipient->getKey())
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $recipientIds
     * @return array<int, int>
     */
    private function resolveTechnicalMaterialSources(
        LetterResponseDossier $dossier,
        array $recipientIds,
    ): array {
        $documents = LetterResponseDocument::query()
            ->where('letter_response_dossier_id', $dossier->getKey())
            ->where('kind', LetterResponseDocumentKind::TechnicalMaterial->value)
            ->whereIn('source_recipient_id', $recipientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $sourceIds = [];

        foreach ($documents as $document) {
            $latest = LetterResponseDocumentVersion::query()
                ->where('letter_response_document_id', $document->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();

            if (LetterResponseReview::query()->where('document_version_id', $latest->getKey())->exists()) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $sourceIds[] = (int) $latest->getKey();
        }

        return $sourceIds;
    }

    private function ensureSeriesDoesNotExist(
        LetterResponseDossier $dossier,
        LetterResponseDocumentKind $kind,
        int $ownerPositionId,
    ): void {
        if (LetterResponseDocument::query()
            ->where('letter_response_dossier_id', $dossier->getKey())
            ->where('kind', $kind->value)
            ->where('owner_position_id', $ownerPositionId)
            ->exists()) {
            throw LetterResponseStateConflict::staleDossier();
        }
    }

    private function initialExecutivePositionId(IncomingLetter $letter): int
    {
        $positionId = (int) $letter->routes()->orderBy('id')->value('recipient_position_id');

        if ($positionId < 1) {
            throw DispositionStateConflict::inconsistentGraph();
        }

        return $positionId;
    }

    /**
     * @param  list<string>  $publicIds
     * @return array<int, int>
     */
    private function resolveConsolidationSources(LetterResponseDossier $dossier, array $publicIds): array
    {
        $sources = LetterResponseDocumentVersion::query()
            ->with('document')
            ->whereIn('public_id', $publicIds)
            ->lockForUpdate()
            ->get();

        if ($sources->count() !== count(array_unique($publicIds))) {
            throw LetterResponseStateConflict::invalidDocument();
        }

        foreach ($sources as $source) {
            $latestId = LetterResponseDocumentVersion::query()
                ->where('letter_response_document_id', $source->letter_response_document_id)
                ->orderByDesc('version_number')
                ->value('id');

            if ($source->document->kind !== LetterResponseDocumentKind::AssistantProposal
                || (int) $source->document->letter_response_dossier_id !== (int) $dossier->getKey()
                || (int) $latestId !== (int) $source->getKey()
                || LetterResponseReview::query()->where('document_version_id', $source->getKey())->exists()) {
                throw LetterResponseStateConflict::invalidDocument();
            }
        }

        return $sources
            ->map(fn (LetterResponseDocumentVersion $source): int => (int) $source->getKey())
            ->values()
            ->all();
    }

    /**
     * @template T
     *
     * @param  callable(StoredLetterResponseDocument): T  $callback
     * @return T
     */
    private function withStoredFile(
        UploadedFile $file,
        LetterResponseDossier $dossier,
        callable $callback,
    ): mixed {
        $stored = $this->storage->store($file, $dossier->incoming_letter_id);

        try {
            return $callback($stored);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }
}
