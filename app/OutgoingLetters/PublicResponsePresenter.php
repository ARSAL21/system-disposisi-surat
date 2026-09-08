<?php

namespace App\OutgoingLetters;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDossier;
use App\Models\LetterSubmission;
use App\Models\OutgoingLetter;
use Carbon\CarbonInterface;

final class PublicResponsePresenter
{
    /** @return array{status: string, updated_at: string, responses: list<array<string, mixed>>} */
    public function forSubmission(LetterSubmission $submission): array
    {
        $submission->loadMissing([
            'incomingLetter.responseDossier',
            'incomingLetter.outgoingLetters' => fn ($query) => $query
                ->where('status', OutgoingLetterStatus::Delivered->value)
                ->orderBy('letter_date')
                ->orderBy('id'),
            'incomingLetter.outgoingLetters.signatoryPosition:id,name',
            'incomingLetter.outgoingLetters.delivery:id,outgoing_letter_id,delivered_at',
        ]);
        $letter = $submission->incomingLetter;

        if (! $letter instanceof IncomingLetter) {
            return [
                'status' => 'IN_PROCESS',
                'updated_at' => $submission->updated_at?->toISOString() ?? now()->toISOString(),
                'responses' => [],
            ];
        }

        $responses = $letter->outgoingLetters;
        $status = match (true) {
            $responses->isNotEmpty() => 'RESPONSE_AVAILABLE',
            $letter->status === IncomingLetterStatus::Completed => 'PREPARING_RESPONSE',
            default => 'IN_PROCESS',
        };
        $latestDelivery = $responses
            ->map(fn (OutgoingLetter $outgoing): mixed => $outgoing->delivery?->delivered_at)
            ->filter()
            ->sortDesc()
            ->first();
        $updatedAt = $latestDelivery
            ?? $this->dossierUpdatedAt($letter)
            ?? $letter->updated_at
            ?? $submission->updated_at;

        $presentedResponses = $responses->map(function (OutgoingLetter $outgoing) use ($submission): array {
            abort_if($outgoing->outgoing_number === null
                || $outgoing->letter_date === null
                || $outgoing->delivery === null, 409, 'Metadata publikasi surat keluar tidak konsisten.');

            return [
                'public_id' => $outgoing->public_id,
                'outgoing_number' => $outgoing->outgoing_number,
                'letter_date' => $outgoing->letter_date->toDateString(),
                'subject' => $outgoing->subject,
                'signatory_position' => $outgoing->signatoryPosition->name,
                'delivered_at' => $outgoing->delivery->delivered_at->toISOString(),
                'preview_url' => route('public.submissions.responses.preview', [$submission, $outgoing]),
                'download_url' => route('public.submissions.responses.download', [$submission, $outgoing]),
            ];
        })->values()->all();

        return [
            'status' => $status,
            'updated_at' => $updatedAt?->toISOString() ?? now()->toISOString(),
            'responses' => array_values($presentedResponses),
        ];
    }

    private function dossierUpdatedAt(IncomingLetter $letter): ?CarbonInterface
    {
        $dossier = $letter->getRelation('responseDossier');

        return $dossier instanceof LetterResponseDossier ? $dossier->updated_at : null;
    }
}
