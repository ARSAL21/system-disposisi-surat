<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Exceptions\DocumentStorageConflict;
use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterRoutingDocumentController extends Controller
{
    public function preview(
        IncomingLetter $incomingLetter,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewRouting', $incomingLetter);

        return $documentResponse->previewLetterDocument(
            $incomingLetter,
            $this->currentDocument($incomingLetter),
        );
    }

    public function download(
        IncomingLetter $incomingLetter,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewRouting', $incomingLetter);

        return $documentResponse->downloadLetterDocument(
            $incomingLetter,
            $this->currentDocument($incomingLetter),
        );
    }

    private function currentDocument(IncomingLetter $incomingLetter): LetterDocument
    {
        $document = $incomingLetter->currentDocument()
            ->with(['sourceSubmissionDocument.submission', 'replacesDocument'])
            ->first();

        if (! $document instanceof LetterDocument) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        return $document;
    }
}
