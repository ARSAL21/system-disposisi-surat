<?php

namespace App\OutgoingLetters;

use App\Enums\LetterResponseDocumentKind;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDeliveryLink;
use App\Models\OutgoingLetterDocumentReview;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\OutgoingLetterManualSignatureReview;
use App\Models\StandaloneOutgoingCopyRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class OutgoingLetterPresenter
{
    /** @return array<string, mixed> */
    public function listItem(OutgoingLetter $letter): array
    {
        if ($letter->origin === OutgoingLetterOrigin::Standalone) {
            return $this->standaloneListItem($letter);
        }

        $this->load($letter);

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
            'origin' => OutgoingLetterOrigin::Response->value,
            'source' => $letter->incomingLetter->submission->source->value,
            'incoming_agenda_number' => $letter->incomingLetter->agenda_number,
            'sender_organization_name' => $letter->incomingLetter->senderOrganization->name,
            'outgoing_number' => $letter->outgoing_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'signatory_position' => $letter->signatoryPosition->name,
            'signatory_name' => $letter->signatoryPosition->activeAssignment?->user?->name,
            'authorized_at' => $letter->authorized_at->toISOString(),
            'updated_at' => $letter->updated_at?->toISOString() ?? $letter->authorized_at->toISOString(),
            'next_action_label' => $this->nextAction($letter),
            'originating_unit_name' => null,
            'links' => [
                'detail' => route('back-office.outgoing-letters.show', $letter),
                'sekda_approval' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function detail(OutgoingLetter $letter, User $user): array
    {
        if ($letter->origin === OutgoingLetterOrigin::Standalone) {
            return $this->standaloneDetail($letter, $user);
        }

        $this->load($letter);
        $latest = $letter->documentVersions->sortByDesc('version_number')->first();
        $latestReview = $latest instanceof OutgoingLetterDocumentVersion ? $latest->review : null;
        $dossier = $letter->dossier;
        $canViewDossier = $dossier !== null && Gate::forUser($user)->allows('view', $dossier);
        $documents = $letter->documentVersions->sortByDesc('version_number')->map(
            fn (OutgoingLetterDocumentVersion $version): array => $this->documentVersion($letter, $version),
        )->values()->all();
        $verifiedReview = $letter->documentVersions
            ->pluck('review')
            ->filter(fn (mixed $review): bool => $review instanceof OutgoingLetterDocumentReview
                && $review->decision === OutgoingLetterReviewDecision::Verified)
            ->last();

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
            'origin' => OutgoingLetterOrigin::Response->value,
            'source' => $letter->incomingLetter->submission->source->value,
            'incoming_letter' => [
                'reference' => $letter->incomingLetter->submission->public_id,
                'agenda_number' => $letter->incomingLetter->agenda_number,
                'subject' => $letter->incomingLetter->subject,
                'sender_organization_name' => $letter->incomingLetter->senderOrganization->name,
                'received_at' => $letter->incomingLetter->received_at->toISOString(),
                'dossier_url' => $canViewDossier
                    ? route('back-office.letter-responses.show', $dossier)
                    : null,
            ],
            'standalone_draft' => null,
            'mandate' => [
                'source_document_title' => $this->sourceTitle($letter),
                'source_version_number' => $letter->sourceDocumentVersion->version_number,
                'authorized_by' => $letter->authorizedBy->name,
                'authorized_position' => $letter->authorizedByPositionAssignment->position->name,
                'authorized_at' => $letter->authorized_at->toISOString(),
            ],
            'signatory' => [
                'position_name' => $letter->signatoryPosition->name,
                'official_name' => $letter->signatoryPosition->activeAssignment?->user?->name,
            ],
            'numbering' => [
                'outgoing_number' => $letter->outgoing_number,
                'agenda_year' => $letter->agenda_year,
                'letter_date' => $letter->letter_date?->toDateString(),
                'numbered_by' => $letter->numberedBy?->name,
                'numbered_at' => $letter->numbered_at?->toISOString(),
            ],
            'documents' => $documents,
            'verification' => [
                'verified_by' => $verifiedReview?->decidedBy?->name,
                'verified_position' => $verifiedReview?->decidedByPositionAssignment?->position?->name,
                'verified_at' => $verifiedReview?->created_at?->toISOString(),
                'note' => $verifiedReview?->note,
            ],
            'delivery' => [
                'method' => $letter->delivery?->method?->value,
                'recipient_name' => $letter->delivery?->recipient_name,
                'delivered_by' => $letter->delivery?->deliveredBy?->name,
                'delivered_at' => $letter->delivery?->delivered_at?->toISOString(),
                'tracking_number' => $letter->delivery?->tracking_number,
                'note' => $letter->delivery?->note,
            ],
            'withdrawal' => $letter->status === OutgoingLetterStatus::Withdrawn ? [
                'reason' => $letter->withdrawal_reason,
                'withdrawn_by' => $letter->withdrawnBy?->name,
                'withdrawn_at' => $letter->withdrawn_at?->toISOString(),
            ] : null,
            'history' => $this->history($letter),
            'capabilities' => [
                'can_assign_number' => $dossier?->status?->value === 'FINALIZED'
                    && $letter->status === OutgoingLetterStatus::Authorized
                    && Gate::forUser($user)->allows('assignNumber', $letter),
                'can_upload_signed_document' => $dossier?->status?->value === 'FINALIZED'
                    && ($letter->status === OutgoingLetterStatus::NumberAssigned
                        || ($letter->status === OutgoingLetterStatus::SignedDocumentUploaded
                            && $latestReview?->decision === OutgoingLetterReviewDecision::Returned))
                    && Gate::forUser($user)->allows('uploadSignedDocument', $letter),
                'can_verify' => $dossier?->status?->value === 'FINALIZED'
                    && $letter->status === OutgoingLetterStatus::SignedDocumentUploaded
                    && $latest instanceof OutgoingLetterDocumentVersion
                    && $latestReview === null
                    && Gate::forUser($user)->allows('verify', $letter),
                'can_request_document_revision' => $dossier?->status?->value === 'FINALIZED'
                    && $letter->status === OutgoingLetterStatus::SignedDocumentUploaded
                    && $latest instanceof OutgoingLetterDocumentVersion
                    && $latestReview === null
                    && Gate::forUser($user)->allows('verify', $letter),
                'can_deliver' => $dossier?->status?->value === 'FINALIZED'
                    && $letter->status === OutgoingLetterStatus::AdminVerified
                    && Gate::forUser($user)->allows('deliver', $letter),
                'can_withdraw' => in_array($dossier?->status?->value, ['OPEN', 'FINALIZED'], true)
                    && $letter->status === OutgoingLetterStatus::Authorized
                    && Gate::forUser($user)->allows('withdraw', $letter),
                'can_select_sekda_approval' => false,
                'can_upload_manual_scan' => false,
                'can_review_manual_scan' => false,
                'can_return_for_revision' => false,
            ],
            'routes' => [
                'index' => route('back-office.outgoing-letters.index'),
                'assign_number' => route('back-office.outgoing-letters.assign-number', $letter),
                'upload_signed_document' => route('back-office.outgoing-letters.documents.store', $letter),
                'verify' => route('back-office.outgoing-letters.verify', $letter),
                'request_document_revision' => route('back-office.outgoing-letters.return-document', $letter),
                'deliver' => route('back-office.outgoing-letters.deliver', $letter),
                'withdraw' => route('back-office.outgoing-letters.withdraw', $letter),
                'sekda_approval' => null,
                'approve_qr' => null,
                'choose_manual_signature' => null,
                'upload_manual_scan' => null,
                'review_manual_scan' => null,
                'return_for_revision' => null,
            ],
        ];
    }

    private function load(OutgoingLetter $letter): void
    {
        $letter->loadMissing([
            'incomingLetter.submission:id,public_id,source,submitted_by_user_id',
            'incomingLetter.senderOrganization:id,name',
            'dossier:id,public_id,incoming_letter_id,status,finalized_at,fulfilled_at',
            'sourceDocumentVersion.document.ownerPosition:id,name',
            'signatoryPosition.activeAssignment.user:id,name',
            'authorizedBy:id,name',
            'authorizedByPositionAssignment.position:id,name',
            'numberedBy:id,name',
            'withdrawnBy:id,name',
            'documentVersions.uploadedBy:id,name',
            'documentVersions.uploadedByPositionAssignment.position:id,name',
            'documentVersions.review.decidedBy:id,name',
            'documentVersions.review.decidedByPositionAssignment.position:id,name',
            'delivery.deliveredBy:id,name',
        ]);
    }

    /** @return array<string, mixed> */
    private function standaloneListItem(OutgoingLetter $letter): array
    {
        $letter->loadMissing([
            'standaloneDraft.organizationalUnit:id,name',
            'signatoryPosition.activeAssignment.user:id,name',
        ]);
        $draft = $letter->standaloneDraft;

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
            'origin' => OutgoingLetterOrigin::Standalone->value,
            'source' => null,
            'incoming_agenda_number' => null,
            'sender_organization_name' => null,
            'originating_unit_name' => $draft?->organizationalUnit?->name,
            'outgoing_number' => $letter->outgoing_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'signatory_position' => $letter->signatoryPosition->name,
            'signatory_name' => $letter->signatoryPosition->activeAssignment?->user?->name,
            'authorized_at' => $letter->authorized_at->toISOString(),
            'updated_at' => $letter->updated_at?->toISOString() ?? $letter->authorized_at->toISOString(),
            'next_action_label' => $this->standaloneNextAction($letter),
            'links' => [
                'detail' => route('back-office.outgoing-letters.show', $letter),
                'sekda_approval' => $this->isStandaloneApprovalStage($letter)
                    ? route('back-office.outgoing-letters.standalone.approvals.show', $letter)
                    : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function standaloneDetail(OutgoingLetter $letter, User $user): array
    {
        $letter->loadMissing([
            'standaloneDraft.organizationalUnit:id,name',
            'standaloneDraft.templateVersion.template:id,name',
            'standaloneDraft.copyRecipients.position:id,name',
            'standaloneDraft.currentDocumentVersion',
            'authorizedBy:id,name',
            'authorizedByPositionAssignment.position:id,name',
            'numberedBy:id,name',
            'signatoryPosition.activeAssignment.user:id,name',
            'documentVersions.uploadedBy:id,name',
            'documentVersions.uploadedByPositionAssignment.position:id,name',
            'documentVersions.manualSignatureReview.reviewedBy:id,name',
            'documentVersions.manualSignatureReview.reviewedByPositionAssignment.position:id,name',
            'sekdaDecisions.decidedBy:id,name',
            'delivery.deliveredBy:id,name',
            'delivery.links.revocation',
            'delivery.internalCopyNotifications.position:id,name',
            'delivery.internalCopyNotifications.recipientUser:id,name',
        ]);
        $draft = $letter->standaloneDraft;
        abort_unless($draft !== null, 404);
        $latest = $letter->documentVersions->sortByDesc('version_number')->first();
        $manualReview = $latest instanceof OutgoingLetterDocumentVersion ? $latest->manualSignatureReview : null;
        $emailLink = $letter->delivery?->links->first();
        $canCreateCorrection = $letter->status === OutgoingLetterStatus::Delivered
            && Gate::forUser($user)->allows('createStandaloneCorrection', $letter);
        $canResendEmail = $letter->delivery?->method?->value === 'EMAIL'
            && Gate::forUser($user)->allows('resendStandaloneDeliveryEmail', $letter);
        $canRevokeEmail = $canResendEmail && $emailLink !== null && $emailLink->revocation === null && ! $emailLink->expires_at->isPast();

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
            'origin' => OutgoingLetterOrigin::Standalone->value,
            'source' => null,
            'incoming_letter' => null,
            'standalone_draft' => [
                'public_id' => $draft->public_id,
                'originating_unit_name' => $draft->organizationalUnit->name,
                'recipient_name' => $draft->recipient_name,
                'recipient_organization' => $draft->recipient_organization,
                'recipient_position' => $draft->recipient_position,
                'copy_recipients' => $draft->copyRecipients->map(fn ($copy): string => $copy->position->name)->values()->all(),
                'template_name' => $draft->templateVersion->template->name,
                'template_version_number' => $draft->templateVersion->version_number,
                'concept_version_number' => $draft->currentDocumentVersion?->version_number,
                'concept_sha256_fingerprint' => $draft->currentDocumentVersion?->sha256,
            ],
            'mandate' => [
                'source_document_title' => 'Konsep surat keluar mandiri',
                'source_version_number' => $draft->currentDocumentVersion->version_number,
                'authorized_by' => $letter->authorizedBy->name,
                'authorized_position' => $letter->authorizedByPositionAssignment->position->name,
                'authorized_at' => $letter->authorized_at->toISOString(),
            ],
            'signatory' => [
                'position_name' => $letter->signatoryPosition->name,
                'official_name' => $letter->signatoryPosition->activeAssignment?->user?->name,
            ],
            'numbering' => [
                'outgoing_number' => $letter->outgoing_number,
                'agenda_year' => $letter->agenda_year,
                'letter_date' => $letter->letter_date?->toDateString(),
                'numbered_by' => $letter->numberedBy?->name,
                'numbered_at' => $letter->numbered_at?->toISOString(),
            ],
            'documents' => $letter->documentVersions->sortByDesc('version_number')->map(
                fn (OutgoingLetterDocumentVersion $version): array => $this->documentVersion($letter, $version),
            )->values()->all(),
            'verification' => [
                'verified_by' => $manualReview?->reviewedBy?->name,
                'verified_position' => $manualReview?->reviewedByPositionAssignment?->position?->name,
                'verified_at' => $manualReview?->created_at?->toISOString(),
                'note' => $manualReview?->note,
            ],
            'delivery' => [
                'method' => $letter->delivery?->method?->value,
                'recipient_email' => $letter->delivery?->method?->value === 'EMAIL' ? $draft->recipient_email : null,
                'recipient_name' => $letter->delivery?->recipient_name,
                'delivered_by' => $letter->delivery?->deliveredBy?->name,
                'delivered_at' => $letter->delivery?->delivered_at?->toISOString(),
                'tracking_number' => $letter->delivery?->tracking_number,
                'note' => $letter->delivery?->note,
                'email' => $this->deliveryEmail($emailLink, $canResendEmail, $canRevokeEmail, $letter),
            ],
            'internal_copies' => $draft->copyRecipients->map(function (StandaloneOutgoingCopyRecipient $copy) use ($letter): array {
                $notice = $letter->delivery?->internalCopyNotifications->firstWhere('position_id', $copy->position_id);

                return [
                    'name' => $notice?->recipientUser->name ?? $copy->position->name,
                    'position' => $copy->position->name,
                    'notified_at' => $notice?->notified_at?->toISOString(),
                    'acknowledged' => $notice !== null,
                ];
            })->values()->all(),
            'withdrawal' => null,
            'history' => $this->standaloneHistory($letter),
            'capabilities' => [
                'can_assign_number' => false,
                'can_upload_signed_document' => false,
                'can_verify' => false,
                'can_request_document_revision' => false,
                'can_deliver' => $letter->status === OutgoingLetterStatus::ReadyForDelivery
                    && Gate::forUser($user)->allows('deliver', $letter),
                'can_withdraw' => false,
                'can_select_sekda_approval' => $this->isStandaloneApprovalStage($letter)
                    && Gate::forUser($user)->allows('view', $letter),
                'can_upload_manual_scan' => Gate::forUser($user)->allows('uploadStandaloneManualScan', $letter),
                'can_review_manual_scan' => Gate::forUser($user)->allows('reviewStandaloneManualScan', $letter),
                'can_return_for_revision' => Gate::forUser($user)->allows('approveStandaloneBySekda', $letter)
                    || Gate::forUser($user)->allows('reviewStandaloneManualScan', $letter),
                'can_create_correction' => $canCreateCorrection,
                'can_resend_delivery_email' => $canResendEmail,
                'can_revoke_delivery_email' => $canRevokeEmail,
            ],
            'routes' => [
                'index' => route('back-office.outgoing-letters.index'),
                'assign_number' => null,
                'upload_signed_document' => null,
                'verify' => null,
                'request_document_revision' => null,
                'deliver' => route('back-office.outgoing-letters.deliver', $letter),
                'withdraw' => null,
                'sekda_approval' => $this->isStandaloneApprovalStage($letter)
                    ? route('back-office.outgoing-letters.standalone.approvals.show', $letter)
                    : null,
                'approve_qr' => route('back-office.outgoing-letters.standalone.approve-qr', $letter),
                'choose_manual_signature' => route('back-office.outgoing-letters.standalone.choose-manual-signature', $letter),
                'upload_manual_scan' => route('back-office.outgoing-letters.standalone.manual-scan.store', $letter),
                'review_manual_scan' => route('back-office.outgoing-letters.standalone.manual-scan.review', $letter),
                'return_for_revision' => route('back-office.outgoing-letters.standalone.return-for-revision', $letter),
                'create_correction' => $canCreateCorrection
                    ? route('back-office.outgoing-letters.standalone.corrections.store', $letter)
                    : null,
                'resend_delivery_email' => $canResendEmail
                    ? route('back-office.outgoing-letters.standalone.delivery-link.resend', $letter)
                    : null,
                'revoke_delivery_email' => $canRevokeEmail
                    ? route('back-office.outgoing-letters.standalone.delivery-link.revoke', $letter)
                    : null,
            ],
        ];
    }

    /** @return array{status:string,sent_at:?string,expires_at:?string,download_url_status:string,resend_url:?string,revoke_url:?string}|null */
    private function deliveryEmail(?OutgoingLetterDeliveryLink $link, bool $canResend, bool $canRevoke, OutgoingLetter $letter): ?array
    {
        if ($link === null && $letter->delivery?->method?->value !== 'EMAIL') {
            return null;
        }

        $status = $link?->revocation !== null ? 'REVOKED' : ($link?->expires_at?->isPast() ? 'EXPIRED' : ($link === null ? 'NOT_SENT' : 'SENT'));

        return [
            'status' => $status,
            'sent_at' => $link?->sent_at?->toISOString(),
            'expires_at' => $link?->expires_at?->toISOString(),
            'download_url_status' => $status === 'SENT' ? 'Tautan aktif dan hanya dikirim lewat email.' : 'Tidak ada tautan aktif.',
            'resend_url' => $canResend ? route('back-office.outgoing-letters.standalone.delivery-link.resend', $letter) : null,
            'revoke_url' => $canRevoke ? route('back-office.outgoing-letters.standalone.delivery-link.revoke', $letter) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function documentVersion(OutgoingLetter $letter, OutgoingLetterDocumentVersion $version): array
    {
        $manualReview = $this->manualSignatureReview($version);
        $documentReview = $version->review;

        return [
            'public_id' => $version->public_id,
            'version_number' => $version->version_number,
            'original_filename' => $version->original_filename,
            'mime_type' => $version->mime_type,
            'size_bytes' => $version->size_bytes,
            'sha256_fingerprint' => $version->sha256,
            'uploaded_by' => $version->uploadedBy->name,
            'uploaded_at' => $version->created_at->toISOString(),
            'upload_note' => $version->upload_note,
            'review_status' => $documentReview?->decision->value
                ?? $manualReview?->decision->value
                ?? 'PENDING',
            'review_note' => $this->reviewNote($documentReview, $manualReview),
            'links' => [
                'preview' => route('back-office.outgoing-letters.documents.preview', [$letter, $version]),
                'download' => route('back-office.outgoing-letters.documents.download', [$letter, $version]),
            ],
        ];
    }

    private function manualSignatureReview(OutgoingLetterDocumentVersion $version): ?OutgoingLetterManualSignatureReview
    {
        if (! $version->relationLoaded('manualSignatureReview')) {
            return null;
        }

        $relation = $version->getRelation('manualSignatureReview');

        return $relation instanceof OutgoingLetterManualSignatureReview ? $relation : null;
    }

    private function reviewNote(
        ?OutgoingLetterDocumentReview $documentReview,
        ?OutgoingLetterManualSignatureReview $manualReview,
    ): ?string {
        if ($documentReview instanceof OutgoingLetterDocumentReview && $documentReview->note !== null) {
            return $documentReview->note;
        }

        return $manualReview instanceof OutgoingLetterManualSignatureReview
            ? $manualReview->note
            : null;
    }

    /** @return list<array<string, string>> */
    private function history(OutgoingLetter $letter): array
    {
        $entries = [[
            'key' => 'authorized',
            'label' => 'Mandat balasan dibuat',
            'description' => 'Substansi dan penandatangan telah diotorisasi eksekutif.',
            'actor_name' => $letter->authorizedBy->name,
            'actor_position' => $letter->authorizedByPositionAssignment->position->name,
            'occurred_at' => $letter->authorized_at->toISOString(),
            'state' => 'DONE',
        ]];

        if ($letter->numbered_at !== null) {
            $entries[] = [
                'key' => 'numbered', 'label' => 'Nomor resmi diberikan',
                'description' => 'Nomor '.$letter->outgoing_number.' dicatat pada register surat keluar.',
                'actor_name' => $letter->numberedBy->name,
                'actor_position' => 'Petugas Surat Bagian Umum',
                'occurred_at' => $letter->numbered_at->toISOString(), 'state' => 'DONE',
            ];
        }

        foreach ($letter->documentVersions as $version) {
            $entries[] = [
                'key' => 'document-'.$version->public_id, 'label' => 'PDF final versi '.$version->version_number.' diunggah',
                'description' => 'Dokumen final disimpan immutable dan menunggu pemeriksaan administratif.',
                'actor_name' => $version->uploadedBy->name,
                'actor_position' => $version->uploadedByPositionAssignment->position->name,
                'occurred_at' => $version->created_at->toISOString(), 'state' => 'DONE',
            ];

            if ($version->review instanceof OutgoingLetterDocumentReview) {
                $entries[] = [
                    'key' => 'review-'.$version->public_id,
                    'label' => $version->review->decision === OutgoingLetterReviewDecision::Verified
                        ? 'Dokumen final diverifikasi'
                        : 'Perbaikan dokumen diminta',
                    'description' => $version->review->decision === OutgoingLetterReviewDecision::Verified
                        ? 'Kabag Umum menyatakan dokumen sesuai register dan mandat.'
                        : 'Versi baru wajib diunggah tanpa menimpa dokumen lama.',
                    'actor_name' => $version->review->decidedBy->name,
                    'actor_position' => $version->review->decidedByPositionAssignment->position->name,
                    'occurred_at' => $version->review->created_at->toISOString(), 'state' => 'DONE',
                ];
            }
        }

        if ($letter->delivery !== null) {
            $entries[] = [
                'key' => 'delivered', 'label' => 'Surat resmi dikirim',
                'description' => 'Bukti pengiriman telah dicatat dan dokumen dikunci.',
                'actor_name' => $letter->delivery->deliveredBy->name,
                'actor_position' => 'Petugas Surat Bagian Umum',
                'occurred_at' => $letter->delivery->delivered_at->toISOString(), 'state' => 'DONE',
            ];
        }

        if ($letter->withdrawn_at !== null) {
            $entries[] = [
                'key' => 'withdrawn', 'label' => 'Mandat ditarik',
                'description' => 'Mandat ditarik sebelum memperoleh nomor dan tetap disimpan dalam histori.',
                'actor_name' => $letter->withdrawnBy->name,
                'actor_position' => 'Pimpinan eksekutif',
                'occurred_at' => $letter->withdrawn_at->toISOString(), 'state' => 'STOPPED',
            ];
        }

        usort($entries, fn (array $left, array $right): int => strcmp($left['occurred_at'], $right['occurred_at']));

        return $entries;
    }

    private function sourceTitle(OutgoingLetter $letter): string
    {
        $document = $letter->sourceDocumentVersion->document;

        return match ($document->kind) {
            LetterResponseDocumentKind::TechnicalMaterial => 'Bahan teknis '.$document->ownerPosition->name,
            LetterResponseDocumentKind::AssistantProposal => 'Usulan balasan '.$document->ownerPosition->name,
            LetterResponseDocumentKind::ExecutiveConsolidation => 'Konsolidasi '.$document->ownerPosition->name,
        };
    }

    private function nextAction(OutgoingLetter $letter): ?string
    {
        $latest = $letter->documentVersions->sortByDesc('version_number')->first();

        return match ($letter->status) {
            OutgoingLetterStatus::Authorized => 'Berikan nomor resmi',
            OutgoingLetterStatus::NumberAssigned => 'Unggah PDF bertanda tangan',
            OutgoingLetterStatus::SignedDocumentUploaded => $latest instanceof OutgoingLetterDocumentVersion
                && $latest->review?->decision === OutgoingLetterReviewDecision::Returned
                ? 'Unggah versi perbaikan'
                : 'Verifikasi dokumen final',
            OutgoingLetterStatus::AdminVerified => 'Kirim surat resmi',
            OutgoingLetterStatus::Delivered, OutgoingLetterStatus::Withdrawn => null,
            default => null,
        };
    }

    /** @return list<array<string, string>> */
    private function standaloneHistory(OutgoingLetter $letter): array
    {
        $entries = [[
            'key' => 'numbered',
            'label' => 'Nomor resmi diberikan',
            'description' => 'Surat mandiri dicatat pada buku agenda surat keluar.',
            'actor_name' => $letter->numberedBy->name,
            'actor_position' => 'Petugas Surat Bagian Umum',
            'occurred_at' => $letter->numbered_at?->toISOString() ?? $letter->authorized_at->toISOString(),
            'state' => 'DONE',
        ]];

        foreach ($letter->sekdaDecisions as $decision) {
            $entries[] = [
                'key' => 'sekda-'.$decision->public_id,
                'label' => match ($decision->decision->value) {
                    'QR_APPROVED' => 'Disahkan dengan QR oleh Sekda',
                    'MANUAL_SIGNATURE_SELECTED' => 'Tanda tangan fisik dipilih',
                    default => 'Dikembalikan Sekda untuk perbaikan',
                },
                'description' => $decision->note ?: 'Keputusan pengesahan Sekda tercatat pada sistem.',
                'actor_name' => $decision->decidedBy->name,
                'actor_position' => 'Sekretaris Daerah',
                'occurred_at' => $decision->created_at->toISOString(),
                'state' => $decision->decision->value === 'RETURNED' ? 'STOPPED' : 'DONE',
            ];
        }

        foreach ($letter->documentVersions as $version) {
            $entries[] = [
                'key' => 'final-'.$version->public_id,
                'label' => 'PDF final versi '.$version->version_number.' tersedia',
                'description' => $version->upload_note,
                'actor_name' => $version->uploadedBy->name,
                'actor_position' => $version->uploadedByPositionAssignment->position->name,
                'occurred_at' => $version->created_at->toISOString(),
                'state' => 'DONE',
            ];
        }

        usort($entries, fn (array $left, array $right): int => strcmp($left['occurred_at'], $right['occurred_at']));

        return $entries;
    }

    private function standaloneNextAction(OutgoingLetter $letter): ?string
    {
        return match ($letter->status) {
            OutgoingLetterStatus::NumberAssigned => 'Ajukan ke Sekda',
            OutgoingLetterStatus::SekdaReview => 'Menunggu pengesahan Sekda',
            OutgoingLetterStatus::AwaitingManualSignature => 'Menunggu tanda tangan fisik Sekda',
            OutgoingLetterStatus::ManualScanReview => 'Periksa scan tanda tangan',
            OutgoingLetterStatus::ReadyForDelivery => 'Siap dikirim oleh Petugas',
            OutgoingLetterStatus::RevisionRequired => 'Perbaiki konsep dan ajukan ulang',
            default => null,
        };
    }

    private function isStandaloneApprovalStage(OutgoingLetter $letter): bool
    {
        return in_array($letter->status, [
            OutgoingLetterStatus::SekdaReview,
            OutgoingLetterStatus::AwaitingManualSignature,
            OutgoingLetterStatus::ManualScanReview,
            OutgoingLetterStatus::ReadyForDelivery,
            OutgoingLetterStatus::RevisionRequired,
        ], true);
    }
}
