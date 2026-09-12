<?php

namespace App\StandaloneOutgoing;

use App\Enums\StandaloneOutgoingDraftStatus;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class StandaloneOutgoingPresenter
{
    /** @return array<string, mixed> */
    public function listItem(StandaloneOutgoingDraft $draft, User $user): array
    {
        return [
            'public_id' => $draft->public_id,
            'unit' => ['code' => $draft->organizationalUnit->code, 'name' => $draft->organizationalUnit->name],
            'recipient_name' => $draft->recipient_name,
            'recipient_organization' => $draft->recipient_organization,
            'subject' => $draft->subject,
            'status' => $draft->status->value,
            'status_label' => $this->statusLabel($draft->status),
            'current_version_number' => $draft->currentDocumentVersion?->version_number,
            'updated_at' => $draft->updated_at?->toISOString(),
            'created_by' => ['name' => $draft->createdBy->name],
            'links' => ['show' => route('back-office.standalone-outgoing.show', $draft)],
            'can_edit' => Gate::forUser($user)->allows('edit', $draft),
            'can_review_section' => Gate::forUser($user)->allows('reviewSection', $draft),
            'can_review_assistant' => Gate::forUser($user)->allows('reviewAssistant', $draft),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(StandaloneOutgoingDraft $draft, User $user): array
    {
        return [
            ...$this->listItem($draft, $user),
            'recipient' => [
                'name' => $draft->recipient_name,
                'organization' => $draft->recipient_organization,
                'position' => $draft->recipient_position,
                'address' => $draft->recipient_address,
                'email' => $draft->recipient_email,
            ],
            'summary' => $draft->summary,
            'template_version_public_id' => $draft->templateVersion->public_id,
            'template' => [
                'code' => $draft->templateVersion->template->code,
                'name' => $draft->templateVersion->template->name,
                'version_number' => $draft->templateVersion->version_number,
            ],
            'copy_recipients' => $draft->copyRecipients->map(fn ($copy): array => [
                'code' => $copy->position->code,
                'name' => $copy->position->name,
                'unit_name' => $copy->position->organizationalUnit?->name,
            ])->values()->all(),
            'document_versions' => $draft->documentVersions->map(fn ($version): array => [
                'public_id' => $version->public_id,
                'version_number' => $version->version_number,
                'original_filename' => $version->original_filename,
                'mime_type' => $version->mime_type,
                'size_bytes' => $version->size_bytes,
                'sha256' => $version->sha256,
                'revision_note' => $version->revision_note,
                'uploaded_by' => ['name' => $version->uploadedBy->name],
                'created_at' => $version->created_at->toISOString(),
                'links' => [
                    'preview' => route('back-office.standalone-outgoing.documents.preview', [$draft, $version]),
                    'download' => route('back-office.standalone-outgoing.documents.download', [$draft, $version]),
                ],
            ])->values()->all(),
            'reviews' => $draft->reviews->map(fn ($review): array => [
                'stage' => $review->stage->value,
                'stage_label' => $review->stage->value === 'SECTION_HEAD' ? 'Pemeriksaan Kabag' : 'Pemeriksaan Asisten',
                'decision' => $review->decision->value,
                'decision_label' => $review->decision->value === 'APPROVED' ? 'Disetujui' : 'Dikembalikan',
                'reason' => $review->reason,
                'decided_by' => ['name' => $review->decidedBy->name],
                'created_at' => $review->created_at->toISOString(),
            ])->values()->all(),
            'actions' => [
                'update' => Gate::forUser($user)->allows('edit', $draft)
                    ? route('back-office.standalone-outgoing.update', $draft) : null,
                'submit' => Gate::forUser($user)->allows('edit', $draft)
                    ? route('back-office.standalone-outgoing.submit', $draft) : null,
                'document_version' => Gate::forUser($user)->allows('edit', $draft)
                    ? route('back-office.standalone-outgoing.documents.versions.store', $draft) : null,
                'section_review' => Gate::forUser($user)->allows('reviewSection', $draft)
                    ? route('back-office.standalone-outgoing.review.section', $draft) : null,
                'assistant_review' => Gate::forUser($user)->allows('reviewAssistant', $draft)
                    ? route('back-office.standalone-outgoing.review.assistant', $draft) : null,
            ],
        ];
    }

    private function statusLabel(StandaloneOutgoingDraftStatus $status): string
    {
        return match ($status) {
            StandaloneOutgoingDraftStatus::Draft => 'Konsep disiapkan',
            StandaloneOutgoingDraftStatus::SectionReview => 'Menunggu pemeriksaan Kabag',
            StandaloneOutgoingDraftStatus::RevisionRequired => 'Perlu diperbaiki staf',
            StandaloneOutgoingDraftStatus::AssistantReview => 'Menunggu pemeriksaan Asisten',
            StandaloneOutgoingDraftStatus::AwaitingNumber => 'Siap diberi nomor',
            StandaloneOutgoingDraftStatus::NumberAssigned => 'Nomor surat sudah diberikan',
            StandaloneOutgoingDraftStatus::SekdaReview => 'Menunggu pengesahan Sekda',
            StandaloneOutgoingDraftStatus::AwaitingManualSignature => 'Menunggu tanda tangan Sekda',
            StandaloneOutgoingDraftStatus::ManualScanReview => 'Menunggu pemeriksaan scan tanda tangan',
            StandaloneOutgoingDraftStatus::ReadyForDelivery => 'Siap dikirim',
        };
    }
}
