<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Exceptions\DocumentStorageConflict;
use App\Http\Controllers\Controller;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveInboxDocumentController extends Controller
{
    public function preview(
        LetterRoute $letterRoute,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewInbox', $letterRoute);
        $letter = $letterRoute->incomingLetter;

        return $documentResponse->previewLetterDocument(
            $letter,
            $this->currentDocument($letterRoute),
        );
    }

    public function download(
        LetterRoute $letterRoute,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewInbox', $letterRoute);
        $letter = $letterRoute->incomingLetter;

        return $documentResponse->downloadLetterDocument(
            $letter,
            $this->currentDocument($letterRoute),
        );
    }

    private function currentDocument(LetterRoute $letterRoute): LetterDocument
    {
        $document = $letterRoute->incomingLetter->currentDocument()
            ->with(['sourceSubmissionDocument.submission', 'replacesDocument'])
            ->first();

        if (! $document instanceof LetterDocument) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        return $document;
    }
}
