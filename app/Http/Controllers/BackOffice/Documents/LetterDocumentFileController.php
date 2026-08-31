<?php

namespace App\Http\Controllers\BackOffice\Documents;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterDocumentFileController extends Controller
{
    public function preview(
        IncomingLetter $incomingLetter,
        LetterDocument $letterDocument,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewDocumentVersions', $incomingLetter);

        return $documentResponse->previewLetterDocument($incomingLetter, $letterDocument);
    }

    public function download(
        IncomingLetter $incomingLetter,
        LetterDocument $letterDocument,
        PrivateDocumentResponse $documentResponse,
    ): StreamedResponse {
        Gate::authorize('viewDocumentVersions', $incomingLetter);

        return $documentResponse->downloadLetterDocument($incomingLetter, $letterDocument);
    }
}
