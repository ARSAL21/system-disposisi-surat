<?php

namespace App\Http\Resources;

use App\Enums\SubmissionStatus;
use App\Intake\IntakeApprovalTimeline;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\SubmissionDecision;
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use LogicException;

/** @mixin LetterSubmission */
class IntakeApprovalSubmissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->relationLoaded('document') ? $this->document : null;
        $latestReview = $this->latestReview;

        if (! $latestReview instanceof SubmissionReview) {
            throw new LogicException('An approval submission must have a screening review.');
        }

        return [
            'public_id' => $this->public_id,
            'source' => $this->source->value,
            'status' => $this->status->value,
            'sender_organization_name' => $this->sender_organization_name,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'external_letter_number' => $this->external_letter_number,
            'external_letter_date' => $this->external_letter_date?->toDateString(),
            'subject' => $this->subject,
            'summary' => $this->summary,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'document' => $document instanceof SubmissionDocument
                ? $this->documentData($document)
                : null,
            'screening_review' => [
                'checklist' => SubmissionScreeningChecklist::present($latestReview->checklist),
                'note' => $latestReview->note,
                'reviewed_by' => $latestReview->createdBy->name,
                'reviewed_at' => $latestReview->created_at->toISOString(),
            ],
            'latest_decision' => $this->decisionData(),
            'registration' => $this->registrationData(),
            'timeline' => app(IntakeApprovalTimeline::class)->build($this->resource),
            'capabilities' => [
                'can_decide' => $this->status === SubmissionStatus::ReadyForApproval
                    && Gate::allows('decideIntake', $this->resource),
                'can_download_document' => $document instanceof SubmissionDocument
                    && Gate::allows('downloadApprovalDocument', $this->resource),
            ],
            'links' => [
                'show' => route('back-office.intake.approvals.show', $this->resource),
                'decision' => route('back-office.intake.approvals.decisions.store', $this->resource),
                'document_preview' => $document instanceof SubmissionDocument
                    ? route('back-office.intake.approvals.document.show', $this->resource)
                    : null,
                'document_download' => $document instanceof SubmissionDocument
                    ? route('back-office.intake.approvals.document.download', $this->resource)
                    : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function documentData(SubmissionDocument $document): array
    {
        return [
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'sha256' => $document->sha256,
            'uploaded_at' => $document->created_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function decisionData(): ?array
    {
        $decision = $this->relationLoaded('latestDecision')
            ? $this->latestDecision
            : null;

        if (! $decision instanceof SubmissionDecision) {
            return null;
        }

        return [
            'outcome' => $decision->outcome->value,
            'note' => $decision->note,
            'decided_by' => $decision->createdBy->name,
            'decided_at' => $decision->created_at->toISOString(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function registrationData(): ?array
    {
        $incomingLetter = $this->relationLoaded('incomingLetter')
            ? $this->incomingLetter
            : null;

        if (! $incomingLetter instanceof IncomingLetter) {
            return null;
        }

        $officialDocument = $incomingLetter->relationLoaded('initialDocument')
            ? $incomingLetter->initialDocument
            : null;

        return [
            'agenda_number' => $incomingLetter->agenda_number,
            'agenda_year' => $incomingLetter->agenda_year,
            'sender_organization_name' => $incomingLetter->senderOrganization->name,
            'registered_at' => $incomingLetter->received_at->toISOString(),
            'official_document' => $officialDocument instanceof LetterDocument
                ? $this->officialDocumentData($officialDocument)
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function officialDocumentData(LetterDocument $document): array
    {
        return [
            'version_number' => $document->version_number,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'sha256' => $document->sha256,
            'recorded_at' => $document->created_at->toISOString(),
            'source' => 'SUBMISSION_DOCUMENT',
        ];
    }
}
