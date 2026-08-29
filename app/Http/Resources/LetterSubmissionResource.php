<?php

namespace App\Http\Resources;

use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionDecision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/** @mixin LetterSubmission */
class LetterSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'revision_note' => $this->status === SubmissionStatus::RevisionRequired
                && $this->relationLoaded('latestReview')
                ? $this->latestReview?->note
                : null,
            'rejection_note' => $this->status === SubmissionStatus::Rejected
                && $this->relationLoaded('latestDecision')
                && $this->latestDecision instanceof SubmissionDecision
                ? $this->latestDecision->note
                : null,
            'document' => $this->whenLoaded('document', function (): ?array {
                if (! $this->document instanceof SubmissionDocument) {
                    return null;
                }

                return [
                    'original_filename' => $this->document->original_filename,
                    'mime_type' => $this->document->mime_type,
                    'size_bytes' => $this->document->size_bytes,
                    'uploaded_at' => $this->document->created_at?->toISOString(),
                ];
            }),
            'capabilities' => $this->capabilities(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function capabilities(): array
    {
        $isDraft = $this->status === SubmissionStatus::Draft;
        $isPubliclyEditable = $this->isPubliclyEditable();
        $hasDocument = $this->relationLoaded('document')
            && $this->document instanceof SubmissionDocument;

        return [
            'can_update' => $isPubliclyEditable && Gate::allows('update', $this->resource),
            'can_replace_document' => $isPubliclyEditable && Gate::allows('replaceDocument', $this->resource),
            'can_submit' => $isPubliclyEditable && $hasDocument && Gate::allows('submit', $this->resource),
            'can_delete' => $isDraft && Gate::allows('delete', $this->resource),
            'can_download_document' => $hasDocument && Gate::allows('downloadDocument', $this->resource),
        ];
    }
}
