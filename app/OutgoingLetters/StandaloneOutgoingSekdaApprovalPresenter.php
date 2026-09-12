<?php

namespace App\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class StandaloneOutgoingSekdaApprovalPresenter
{
    /** @return array<string, mixed> */
    public function present(OutgoingLetter $letter, User $user): array
    {
        $letter->loadMissing([
            'standaloneDraft.organizationalUnit:id,name',
            'standaloneDraft.templateVersion.template:id,name',
            'standaloneDraft.copyRecipients.position:id,name',
            'standaloneDraft.currentDocumentVersion',
            'documentVersions.uploadedBy:id,name',
            'documentVersions.manualSignatureReview.reviewedBy:id,name',
            'electronicApproval.finalDocumentVersion',
        ]);
        $draft = $letter->standaloneDraft;
        abort_unless($draft !== null, 404);
        $source = $draft->currentDocumentVersion;
        $manualScan = $letter->documentVersions->sortByDesc('version_number')->first();
        $review = $manualScan instanceof OutgoingLetterDocumentVersion ? $manualScan->manualSignatureReview : null;
        $final = $letter->electronicApproval?->finalDocumentVersion;
        $visible = $final instanceof OutgoingLetterDocumentVersion ? $final : $manualScan;

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
            'outgoing_number' => $letter->outgoing_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'originating_unit_name' => $draft->organizationalUnit->name,
            'recipient' => [
                'name' => $draft->recipient_name,
                'organization' => $draft->recipient_organization,
                'position' => $draft->recipient_position,
            ],
            'copy_recipients' => $draft->copyRecipients->map(fn ($recipient): string => $recipient->position->name)->values()->all(),
            'current_document' => $visible instanceof OutgoingLetterDocumentVersion
                ? $this->outgoingDocument($letter, $visible)
                : ($source instanceof StandaloneOutgoingDocumentVersion ? $this->sourceDocument($letter, $source) : null),
            'qr_placement' => [
                'page_label' => $draft->templateVersion->qr_page_mode === 'LAST_PAGE'
                    ? 'Halaman terakhir'
                    : 'Halaman '.$draft->templateVersion->qr_page_number,
                'x_ratio' => (float) $draft->templateVersion->qr_x_ratio,
                'y_ratio' => (float) $draft->templateVersion->qr_y_ratio,
                'width_ratio' => (float) $draft->templateVersion->qr_width_ratio,
                'height_ratio' => (float) $draft->templateVersion->qr_height_ratio,
            ],
            'manual_scan' => $manualScan instanceof OutgoingLetterDocumentVersion && $letter->electronicApproval?->method?->value === 'MANUAL_SIGNATURE'
                ? [
                    'version_number' => $manualScan->version_number,
                    'sha256_fingerprint' => $manualScan->sha256,
                    'uploaded_at' => $manualScan->created_at->toISOString(),
                    'uploaded_by' => $manualScan->uploadedBy->name,
                    'preview_url' => route('back-office.outgoing-letters.documents.preview', [$letter, $manualScan]),
                ]
                : null,
            'review' => $review === null ? null : [
                'decision' => $review->decision->value,
                'note' => $review->note,
                'reviewed_by' => $review->reviewedBy->name,
                'reviewed_at' => $review->created_at->toISOString(),
            ],
            'capabilities' => [
                'can_approve_qr' => $letter->status === OutgoingLetterStatus::SekdaReview
                    && Gate::forUser($user)->allows('approveStandaloneBySekda', $letter),
                'can_choose_manual_signature' => $letter->status === OutgoingLetterStatus::SekdaReview
                    && Gate::forUser($user)->allows('approveStandaloneBySekda', $letter),
                'can_upload_manual_scan' => $letter->status === OutgoingLetterStatus::AwaitingManualSignature
                    && Gate::forUser($user)->allows('uploadStandaloneManualScan', $letter),
                'can_review_manual_scan' => $letter->status === OutgoingLetterStatus::ManualScanReview
                    && Gate::forUser($user)->allows('reviewStandaloneManualScan', $letter),
                'can_return_for_revision' => ($letter->status === OutgoingLetterStatus::SekdaReview
                        && Gate::forUser($user)->allows('approveStandaloneBySekda', $letter))
                    || ($letter->status === OutgoingLetterStatus::ManualScanReview
                        && Gate::forUser($user)->allows('reviewStandaloneManualScan', $letter)),
            ],
            'routes' => [
                'index' => route('back-office.outgoing-letters.index'),
                'approve_qr' => route('back-office.outgoing-letters.standalone.approve-qr', $letter),
                'choose_manual_signature' => route('back-office.outgoing-letters.standalone.choose-manual-signature', $letter),
                'upload_manual_scan' => route('back-office.outgoing-letters.standalone.manual-scan.store', $letter),
                'review_manual_scan' => route('back-office.outgoing-letters.standalone.manual-scan.review', $letter),
                'return_for_revision' => route('back-office.outgoing-letters.standalone.return-for-revision', $letter),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function sourceDocument(OutgoingLetter $letter, StandaloneOutgoingDocumentVersion $version): array
    {
        return [
            'version_number' => $version->version_number,
            'sha256_fingerprint' => $version->sha256,
            'preview_url' => route('back-office.outgoing-letters.standalone.source.preview', [$letter, $version]),
            'download_url' => route('back-office.outgoing-letters.standalone.source.download', [$letter, $version]),
        ];
    }

    /** @return array<string, mixed> */
    private function outgoingDocument(OutgoingLetter $letter, OutgoingLetterDocumentVersion $version): array
    {
        return [
            'version_number' => $version->version_number,
            'sha256_fingerprint' => $version->sha256,
            'preview_url' => route('back-office.outgoing-letters.documents.preview', [$letter, $version]),
            'download_url' => route('back-office.outgoing-letters.documents.download', [$letter, $version]),
        ];
    }
}
