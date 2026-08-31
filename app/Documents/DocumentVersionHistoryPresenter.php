<?php

namespace App\Documents;

use App\Enums\SubmissionSource;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class DocumentVersionHistoryPresenter
{
    /** @return array<string, mixed> */
    public function letter(IncomingLetter $incomingLetter): array
    {
        return [
            'id' => $incomingLetter->getKey(),
            'agenda_number' => $incomingLetter->agenda_number,
            'agenda_year' => $incomingLetter->agenda_year,
            'subject' => $incomingLetter->subject,
            'status' => $incomingLetter->status->value,
            'received_at' => $incomingLetter->received_at->toISOString(),
        ];
    }

    /**
     * @param  Collection<int, LetterDocument>  $versions
     * @param  Collection<int, AuditLog>  $audits
     * @return list<array<string, mixed>>
     */
    public function versions(Collection $versions, Collection $audits): array
    {
        $currentVersionNumber = (int) $versions->max('version_number');

        return array_values($versions
            ->map(fn (LetterDocument $document): array => $this->version(
                $document,
                $audits->get($document->getKey()),
                $currentVersionNumber,
            ))
            ->all());
    }

    /** @return array<string, mixed> */
    private function version(
        LetterDocument $document,
        ?AuditLog $audit,
        int $currentVersionNumber,
    ): array {
        return [
            'id' => $document->getKey(),
            'version_number' => $document->version_number,
            'is_current' => $document->version_number === $currentVersionNumber,
            'replaces_version_number' => $document->replacesDocument?->version_number,
            'source' => $this->source($document),
            'original_filename' => $this->safeFilename($document->original_filename),
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'sha256' => strtolower($document->sha256),
            'correction_reason' => $document->correction_reason,
            'uploaded_by' => [
                'id' => $document->uploadedBy->getKey(),
                'name' => Str::limit($document->uploadedBy->name, 150, ''),
                'position' => null,
                'unit' => null,
            ],
            'recorded_by' => $this->recordedBy($audit),
            'created_at' => $document->created_at->toISOString(),
            'preview_url' => route('back-office.letters.documents.preview', [
                'incomingLetter' => $document->incoming_letter_id,
                'letterDocument' => $document,
            ]),
            'download_url' => route('back-office.letters.documents.download', [
                'incomingLetter' => $document->incoming_letter_id,
                'letterDocument' => $document,
            ]),
        ];
    }

    private function source(LetterDocument $document): string
    {
        if ($document->source_submission_document_id === null) {
            return 'MANUAL_CORRECTION';
        }

        return $document->sourceSubmissionDocument?->submission?->source === SubmissionSource::Manual
            ? 'MANUAL_INTAKE'
            : 'ONLINE_SUBMISSION';
    }

    /** @return array{id: int, name: string, position: string|null, unit: string|null}|null */
    private function recordedBy(?AuditLog $audit): ?array
    {
        $actor = $audit?->actor;

        if (! $actor instanceof User || ! $actor->isInternalAccount()) {
            return null;
        }

        $position = $audit->actorPositionAssignment?->position;

        return [
            'id' => $actor->getKey(),
            'name' => Str::limit($actor->name, 150, ''),
            'position' => $position === null ? null : Str::limit($position->name, 150, ''),
            'unit' => $position?->organizationalUnit === null
                ? null
                : Str::limit($position->organizationalUnit->name, 150, ''),
        ];
    }

    private function safeFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $basename) ?: 'document.pdf';

        return Str::limit($sanitized, 255, '');
    }
}
