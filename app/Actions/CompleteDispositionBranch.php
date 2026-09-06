<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDocumentKind;
use App\Exceptions\DispositionStateConflict;
use App\LetterResponses\LetterResponseDocumentVersionWriter;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Services\DispositionAggregateStateService;
use App\Services\DispositionBranchLockService;
use App\Services\LetterResponseDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CompleteDispositionBranch
{
    public function __construct(
        private readonly DispositionBranchLockService $lockService,
        private readonly DispositionAggregateStateService $aggregateStateService,
        private readonly RecordAudit $recordAudit,
        private readonly OpenLetterResponseDossier $openResponseDossier,
        private readonly LetterResponseDocumentStorage $responseDocumentStorage,
        private readonly LetterResponseDocumentVersionWriter $responseDocumentWriter,
    ) {}

    public function execute(
        User $actor,
        DispositionRecipient $branch,
        string $completionNote,
        ?UploadedFile $technicalDocument = null,
        ?string $technicalDocumentNote = null,
    ): DispositionRecipient {
        $storedDocument = null;

        if ($technicalDocument instanceof UploadedFile) {
            $letterId = (int) $branch->disposition()->value('incoming_letter_id');

            if ($letterId < 1) {
                throw DispositionStateConflict::inconsistentGraph();
            }

            $storedDocument = $this->responseDocumentStorage->store($technicalDocument, $letterId);
        }

        try {
            return DB::transaction(function () use ($actor, $branch, $completionNote, $storedDocument, $technicalDocumentNote): DispositionRecipient {
                $context = $this->lockService->lock($actor, $branch);
                $previousStatus = $context->branch->status;

                if (! in_array($previousStatus, [
                    DispositionRecipientStatus::Pending,
                    DispositionRecipientStatus::InProgress,
                ], true)) {
                    throw DispositionStateConflict::staleBranch();
                }

                $context->branch->status = DispositionRecipientStatus::Completed;
                $context->branch->completed_at = Date::now();
                $context->branch->completed_by_user_id = $context->actor->getKey();
                $context->branch->completed_by_position_assignment_id = $context->actorPositionAssignment->getKey();
                $context->branch->completion_note = $completionNote;
                $context->branch->save();

                $dossier = $this->openResponseDossier->executeLocked(
                    $context->letter,
                    $context->actor,
                    $context->actorPositionAssignment,
                );

                if ($storedDocument !== null) {
                    $this->responseDocumentWriter->write(
                        dossier: $dossier,
                        kind: LetterResponseDocumentKind::TechnicalMaterial,
                        ownerPositionId: $context->branch->recipient_position_id,
                        sourceRecipientId: $context->branch->getKey(),
                        actor: $context->actor,
                        assignment: $context->actorPositionAssignment,
                        stored: $storedDocument,
                        revisionNote: $technicalDocumentNote,
                    );
                }

                $this->recordAudit->execute(
                    actor: $context->actor,
                    action: AuditAction::DispositionCompleted,
                    subjectType: 'disposition_recipient',
                    subjectId: $context->branch->getKey(),
                    oldValues: ['recipient_status' => $previousStatus->value],
                    newValues: ['recipient_status' => DispositionRecipientStatus::Completed->value],
                    metadata: [
                        'incoming_letter_id' => $context->letter->getKey(),
                        'disposition_id' => $context->branch->disposition_id,
                    ],
                    actorPositionAssignment: $context->actorPositionAssignment,
                );

                if ($this->aggregateStateService->completeLetterWhenAllBranchesComplete(
                    $context->letter,
                    $context->terminalBranches,
                )) {
                    $this->recordAudit->execute(
                        actor: $context->actor,
                        action: AuditAction::LetterCompleted,
                        subjectType: 'incoming_letter',
                        subjectId: $context->letter->getKey(),
                        oldValues: ['letter_status' => IncomingLetterStatus::InProgress->value],
                        newValues: ['letter_status' => IncomingLetterStatus::Completed->value],
                        metadata: [
                            'terminal_branch_count' => $context->terminalBranches->count(),
                        ],
                        actorPositionAssignment: $context->actorPositionAssignment,
                    );
                }

                return $context->branch;
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($storedDocument !== null) {
                $this->responseDocumentStorage->delete($storedDocument);
            }

            throw $exception;
        }
    }
}
