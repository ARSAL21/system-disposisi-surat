<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Exceptions\DocumentStorageConflict;
use App\Http\Controllers\Controller;
use App\Models\DispositionRecipient;
use App\Models\LetterDocument;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispositionInboxDocumentController extends Controller
{
    public function preview(
        DispositionRecipient $dispositionRecipient,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewInbox', $dispositionRecipient);
        $letter = $dispositionRecipient->disposition->incomingLetter;

        return $documentResponse->previewLetterDocument(
            $letter,
            $this->currentDocument($dispositionRecipient),
        );
    }

    public function download(
        DispositionRecipient $dispositionRecipient,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewInbox', $dispositionRecipient);
        $letter = $dispositionRecipient->disposition->incomingLetter;

        return $documentResponse->downloadLetterDocument(
            $letter,
            $this->currentDocument($dispositionRecipient),
        );
    }

    private function currentDocument(DispositionRecipient $recipient): LetterDocument
    {
        $document = $recipient->disposition->incomingLetter->currentDocument()
            ->with(['sourceSubmissionDocument.submission', 'replacesDocument'])
            ->first();

        if (! $document instanceof LetterDocument) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        return $document;
    }
}
