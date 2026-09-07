<?php

namespace App\OutgoingLetters;

use App\Enums\LetterResponseDocumentKind;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentReview;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class OutgoingLetterPresenter
{
    /** @return array<string, mixed> */
    public function listItem(OutgoingLetter $letter): array
    {
        $this->load($letter);

        return [
            'public_id' => $letter->public_id,
            'subject' => $letter->subject,
            'status' => $letter->status->value,
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
            'links' => ['detail' => route('back-office.outgoing-letters.show', $letter)],
        ];
    }

    /** @return array<string, mixed> */
    public function detail(OutgoingLetter $letter, User $user): array
    {
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
            ],
            'routes' => [
                'index' => route('back-office.outgoing-letters.index'),
                'assign_number' => route('back-office.outgoing-letters.assign-number', $letter),
                'upload_signed_document' => route('back-office.outgoing-letters.documents.store', $letter),
                'verify' => route('back-office.outgoing-letters.verify', $letter),
                'request_document_revision' => route('back-office.outgoing-letters.return-document', $letter),
                'deliver' => route('back-office.outgoing-letters.deliver', $letter),
                'withdraw' => route('back-office.outgoing-letters.withdraw', $letter),
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
    private function documentVersion(OutgoingLetter $letter, OutgoingLetterDocumentVersion $version): array
    {
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
            'review_status' => $version->review?->decision->value ?? 'PENDING',
            'review_note' => $version->review?->note,
            'links' => [
                'preview' => route('back-office.outgoing-letters.documents.preview', [$letter, $version]),
                'download' => route('back-office.outgoing-letters.documents.download', [$letter, $version]),
            ],
        ];
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
        };
    }
}
