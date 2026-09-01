<?php

namespace App\Http\Resources\BackOffice;

use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\SubmissionDecision;
use App\Models\SubmissionDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LetterSubmission */
class IntakeDashboardItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->relationLoaded('document') ? $this->document : null;
        $hasDocument = $document instanceof SubmissionDocument;

        $internalRevisionNote = null;
        if ($this->status === SubmissionStatus::InternalRevisionRequired
            && $this->relationLoaded('latestDecision')
            && $this->latestDecision instanceof SubmissionDecision) {
            $internalRevisionNote = $this->latestDecision->note;
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
            'document' => $hasDocument ? [
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'size_bytes' => (int) $document->size_bytes,
            ] : null,
            'internal_revision_note' => $internalRevisionNote,
            'links' => [
                'show' => route('back-office.intake.submissions.show', $this->resource),
                'document_preview' => $hasDocument
                    ? route('back-office.intake.submissions.document.show', $this->resource)
                    : null,
                'document_download' => $hasDocument
                    ? route('back-office.intake.submissions.document.download', $this->resource)
                    : null,
            ],
        ];
    }
}
