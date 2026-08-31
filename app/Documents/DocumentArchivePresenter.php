<?php

namespace App\Documents;

use App\Exceptions\DocumentStorageConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;

final class DocumentArchivePresenter
{
    /** @return array<string, mixed> */
    public function present(IncomingLetter $letter): array
    {
        $currentDocument = $letter->currentDocument;

        if (! $currentDocument instanceof LetterDocument) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        return [
            'id' => $letter->getKey(),
            'agenda_number' => $letter->agenda_number,
            'agenda_year' => $letter->agenda_year,
            'subject' => $letter->subject,
            'sender_organization_name' => $letter->senderOrganization->name,
            'received_at' => $letter->received_at->toISOString(),
            'status' => $letter->status->value,
            'total_versions' => (int) ($letter->documents_count ?? 0),
            'current_version' => [
                'version_number' => $currentDocument->version_number,
                'original_filename' => $currentDocument->original_filename,
                'size_bytes' => $currentDocument->size_bytes,
                'sha256' => strtolower($currentDocument->sha256),
                'created_at' => $currentDocument->created_at->toISOString(),
            ],
            'links' => [
                'history' => route('back-office.letters.documents.index', $letter),
            ],
        ];
    }
}
