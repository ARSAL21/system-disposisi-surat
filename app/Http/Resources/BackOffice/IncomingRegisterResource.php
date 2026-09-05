<?php

namespace App\Http\Resources\BackOffice;

use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/** @mixin IncomingLetter */
class IncomingRegisterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $document = $this->relationLoaded('currentDocument') ? $this->currentDocument : null;
        $canViewDocumentHistory = Gate::allows('viewDocumentVersions', $this->resource);

        return [
            'id' => $this->getKey(),
            'agenda_number' => $this->agenda_number,
            'agenda_year' => $this->agenda_year,
            'source' => $this->submission->source->value,
            'received_at' => $this->received_at->toISOString(),
            'sender_organization_name' => $this->senderOrganization->name,
            'contact_name' => $this->submission->contact_name,
            'external_letter_number' => $this->external_letter_number,
            'external_letter_date' => $this->external_letter_date?->toDateString(),
            'subject' => $this->subject,
            'status' => $this->status->value,
            'document' => $document instanceof LetterDocument
                ? [
                    'original_filename' => $document->original_filename,
                    'size_bytes' => $document->size_bytes,
                ]
                : null,
            'links' => [
                'document_history' => $canViewDocumentHistory
                    ? route('back-office.letters.documents.index', $this->resource)
                    : null,
            ],
        ];
    }
}
