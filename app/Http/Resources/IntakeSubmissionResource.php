<?php

namespace App\Http\Resources;

use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use App\Models\SubmissionDecision;
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/** @mixin LetterSubmission */
class IntakeSubmissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->relationLoaded('document') ? $this->document : null;
        $latestReview = $this->latestLoadedReview();

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
            'document' => $document instanceof SubmissionDocument
                ? $this->documentData($document)
                : null,
            'checklist' => SubmissionScreeningChecklist::present($latestReview?->checklist),
            'latest_note' => $latestReview?->note,
            'internal_revision_note' => $this->internalRevisionNote(),
            'timeline' => $this->timeline(),
            'capabilities' => [
                'can_screen' => in_array($this->status, [
                    SubmissionStatus::Submitted,
                    SubmissionStatus::InternalRevisionRequired,
                ], true)
                    && Gate::allows('screenIntake', $this->resource),
                'can_download_document' => $document instanceof SubmissionDocument
                    && Gate::allows('downloadIntakeDocument', $this->resource),
            ],
            'links' => [
                'show' => route('back-office.intake.submissions.show', $this->resource),
                'screen' => route('back-office.intake.submissions.screen', $this->resource),
                'document_preview' => $document instanceof SubmissionDocument
                    ? route('back-office.intake.submissions.document.show', $this->resource)
                    : null,
                'document_download' => $document instanceof SubmissionDocument
                    ? route('back-office.intake.submissions.document.download', $this->resource)
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

    private function latestLoadedReview(): ?SubmissionReview
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->sortByDesc('id')->first();
        }

        return $this->relationLoaded('latestReview')
            ? $this->latestReview
            : null;
    }

    /** @return list<array{id: string, title: string, description: string, occurred_at: string|null, state: string}> */
    private function timeline(): array
    {
        $sourceLabel = match ($this->source) {
            SubmissionSource::Online => 'portal publik',
            SubmissionSource::Manual => 'pencatatan petugas',
        };

        $items = [[
            'id' => 'created',
            'title' => 'Pengajuan surat dibuat',
            'description' => 'Data awal disimpan melalui '.$sourceLabel.'.',
            'occurred_at' => $this->created_at?->toISOString(),
            'state' => 'complete',
        ]];

        if ($this->submitted_at !== null) {
            $items[] = [
                'id' => 'submitted',
                'title' => 'Dikirim ke Bagian Umum',
                'description' => 'Data surat dan PDF masuk ke antrean pemeriksaan awal.',
                'occurred_at' => $this->submitted_at->toISOString(),
                'state' => 'complete',
            ];
        }

        foreach ($this->loadedReviews() as $review) {
            $items[] = [
                'id' => 'review-'.$review->getKey(),
                'title' => match ($review->outcome) {
                    SubmissionReviewOutcome::RevisionRequired => 'Perbaikan dari pengirim diminta',
                    SubmissionReviewOutcome::ReadyForApproval => 'Lolos pemeriksaan petugas',
                },
                'description' => $review->note ?: match ($review->outcome) {
                    SubmissionReviewOutcome::RevisionRequired => 'Pengirim perlu memperbaiki kelengkapan surat yang diajukan.',
                    SubmissionReviewOutcome::ReadyForApproval => 'Pengajuan surat menunggu keputusan Kepala Bagian Umum.',
                },
                'occurred_at' => $review->created_at->toISOString(),
                'state' => 'complete',
            ];
        }

        foreach ($this->loadedDecisions() as $decision) {
            $items[] = [
                'id' => 'decision-'.$decision->getKey(),
                'title' => 'Dikembalikan oleh Kepala Bagian Umum',
                'description' => $decision->note ?? 'Hasil pemeriksaan perlu disempurnakan secara internal.',
                'occurred_at' => $decision->created_at->toISOString(),
                'state' => 'complete',
            ];
        }

        if (in_array($this->status, [
            SubmissionStatus::Submitted,
            SubmissionStatus::InternalRevisionRequired,
        ], true)) {
            $items[] = [
                'id' => 'awaiting-screening',
                'title' => $this->status === SubmissionStatus::InternalRevisionRequired
                    ? 'Menunggu perbaikan internal'
                    : 'Menunggu pemeriksaan awal',
                'description' => $this->status === SubmissionStatus::InternalRevisionRequired
                    ? 'Petugas perlu menindaklanjuti catatan Kepala Bagian Umum.'
                    : 'Belum ada hasil pemeriksaan staf untuk pengiriman terbaru.',
                'occurred_at' => null,
                'state' => 'current',
            ];
        }

        return $items;
    }

    /** @return Collection<int, SubmissionReview> */
    private function loadedReviews(): Collection
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->sortBy('id')->values();
        }

        $latestReview = $this->latestLoadedReview();

        return new Collection($latestReview === null ? [] : [$latestReview]);
    }

    private function internalRevisionNote(): ?string
    {
        if ($this->status !== SubmissionStatus::InternalRevisionRequired
            || ! $this->relationLoaded('latestDecision')) {
            return null;
        }

        return $this->latestDecision instanceof SubmissionDecision
            ? $this->latestDecision->note
            : null;
    }

    /** @return Collection<int, SubmissionDecision> */
    private function loadedDecisions(): Collection
    {
        if ($this->relationLoaded('decisions')) {
            return $this->decisions->sortBy('id')->values();
        }

        if ($this->relationLoaded('latestDecision')
            && $this->latestDecision instanceof SubmissionDecision) {
            return new Collection([$this->latestDecision]);
        }

        return new Collection;
    }
}
