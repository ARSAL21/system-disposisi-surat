<?php

namespace App\Intake;

use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;

class IntakeApprovalTimeline
{
    /** @return list<array{id: string, title: string, description: string, occurred_at: string|null, state: string}> */
    public function build(LetterSubmission $submission): array
    {
        $items = [[
            'id' => 'created',
            'title' => 'Pengajuan surat dibuat',
            'description' => $submission->source === SubmissionSource::Online
                ? 'Data awal disimpan melalui portal publik.'
                : 'Data awal dicatat oleh petugas Bagian Umum.',
            'occurred_at' => $submission->created_at?->toISOString(),
            'state' => 'complete',
        ]];

        if ($submission->submitted_at !== null) {
            $items[] = [
                'id' => 'submitted',
                'title' => 'Dikirim ke Bagian Umum',
                'description' => 'Surat masuk ke antrean pemeriksaan awal.',
                'occurred_at' => $submission->submitted_at->toISOString(),
                'state' => 'complete',
            ];
        }

        foreach ($submission->reviews->sortBy('id') as $review) {
            $items[] = [
                'id' => 'review-'.$review->getKey(),
                'title' => $review->outcome === SubmissionReviewOutcome::ReadyForApproval
                    ? 'Lolos pemeriksaan petugas'
                    : 'Perbaikan dari pengirim diminta',
                'description' => $review->note ?? 'Hasil pemeriksaan dicatat oleh petugas.',
                'occurred_at' => $review->created_at->toISOString(),
                'state' => 'complete',
            ];
        }

        foreach ($submission->decisions->sortBy('id') as $decision) {
            $items[] = [
                'id' => 'decision-'.$decision->getKey(),
                'title' => match ($decision->outcome) {
                    SubmissionDecisionOutcome::InternalRevisionRequired => 'Dikembalikan kepada petugas',
                    SubmissionDecisionOutcome::Rejected => 'Ditolak secara administratif',
                    SubmissionDecisionOutcome::Registered => 'Diregistrasikan sebagai surat masuk',
                },
                'description' => $decision->note ?? match ($decision->outcome) {
                    SubmissionDecisionOutcome::InternalRevisionRequired => 'Petugas perlu menyempurnakan hasil pemeriksaan.',
                    SubmissionDecisionOutcome::Rejected => 'Pengajuan tidak dilanjutkan.',
                    SubmissionDecisionOutcome::Registered => 'Surat telah memperoleh nomor agenda resmi.',
                },
                'occurred_at' => $decision->created_at->toISOString(),
                'state' => 'complete',
            ];
        }

        if ($submission->status === SubmissionStatus::ReadyForApproval) {
            $items[] = [
                'id' => 'awaiting-decision',
                'title' => 'Menunggu keputusan Kepala Bagian Umum',
                'description' => 'Pengajuan siap mendapatkan keputusan administratif resmi.',
                'occurred_at' => null,
                'state' => 'current',
            ];
        }

        return $items;
    }
}
