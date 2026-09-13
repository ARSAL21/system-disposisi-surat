<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\ExpertConsultationStatus;
use App\Exceptions\DocumentStorageConflict;
use App\Exceptions\ExpertConsultationStateConflict;
use App\ExpertConsultations\ExpertConsultationDocumentStorage;
use App\ExpertConsultations\ExpertConsultationPositionResolver;
use App\ExpertConsultations\StoredExpertConsultationDocument;
use App\Models\ExpertConsultation;
use App\Models\ExpertConsultationDocument;
use App\Models\ExpertConsultationReport;
use App\Models\IncomingLetter;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ReportExpertConsultation
{
    public function __construct(
        private readonly ExpertConsultationPositionResolver $positionResolver,
        private readonly ExpertConsultationDocumentStorage $storage,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, ExpertConsultation $consultation, string $summary, string $recommendation, ?UploadedFile $file): void
    {
        $stored = null;
        try {
            if ($file instanceof UploadedFile) {
                $stored = $this->storage->store($file, (int) $consultation->getKey());
            }
            DB::transaction(function () use ($actor, $consultation, $summary, $recommendation, $stored): void {
                $route = LetterRoute::query()->whereKey($consultation->letter_route_id)->lockForUpdate()->firstOrFail();
                $letter = IncomingLetter::query()->whereKey($route->incoming_letter_id)->lockForUpdate()->firstOrFail();
                $locked = ExpertConsultation::query()->whereKey($consultation->getKey())->lockForUpdate()->firstOrFail();
                if ($locked->status !== ExpertConsultationStatus::Pending || (int) $locked->incoming_letter_id !== (int) $letter->getKey()) {
                    throw ExpertConsultationStateConflict::stale();
                }
                $assignment = $this->positionResolver->lockExpertAssignmentForPosition($actor, $locked->expert_position_id);
                if (ExpertConsultationReport::query()->where('expert_consultation_id', $locked->getKey())->lockForUpdate()->exists()) {
                    throw ExpertConsultationStateConflict::stale();
                }
                $now = Date::now();
                $report = new ExpertConsultationReport;
                $report->expert_consultation_id = $locked->getKey();
                $report->reported_by_user_id = $actor->getKey();
                $report->reported_by_position_assignment_id = $assignment->getKey();
                $report->summary = $summary;
                $report->recommendation = $recommendation;
                $report->created_at = $now;
                $report->save();
                if ($stored instanceof StoredExpertConsultationDocument) {
                    if ($stored->sizeBytes < 1) {
                        throw DocumentStorageConflict::invalidMetadata();
                    }
                    if (ExpertConsultationDocument::query()->where('expert_consultation_id', $locked->getKey())->where('sha256', $stored->sha256)->lockForUpdate()->exists()) {
                        throw ValidationException::withMessages(['document' => 'Berkas pendukung identik sudah tercatat.']);
                    }
                    $document = new ExpertConsultationDocument;
                    $document->expert_consultation_id = $locked->getKey();
                    $document->version_number = 1;
                    $document->replaces_document_id = null;
                    $document->storage_disk = $stored->disk;
                    $document->storage_path = $stored->path;
                    $document->original_filename = $stored->originalFilename;
                    $document->mime_type = $stored->mimeType;
                    $document->size_bytes = $stored->sizeBytes;
                    $document->sha256 = $stored->sha256;
                    $document->uploaded_by_user_id = $actor->getKey();
                    $document->uploaded_by_position_assignment_id = $assignment->getKey();
                    $document->created_at = $now;
                    $document->save();
                }
                $locked->status = ExpertConsultationStatus::Reported;
                $locked->reported_at = $now;
                $locked->save();
                $this->recordAudit->execute($actor, AuditAction::ExpertConsultationReported, 'expert_consultation', $locked->getKey(), oldValues: ['status' => ExpertConsultationStatus::Pending->value], newValues: ['status' => ExpertConsultationStatus::Reported->value, 'has_document' => $stored instanceof StoredExpertConsultationDocument], metadata: ['incoming_letter_id' => $letter->getKey(), 'letter_route_id' => $route->getKey()], actorPositionAssignment: $assignment);
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($stored instanceof StoredExpertConsultationDocument) {
                $this->storage->delete($stored);
            }
            throw $exception;
        }
    }
}
