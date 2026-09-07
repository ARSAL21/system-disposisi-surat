<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OutgoingLetterDocumentFileController extends Controller
{
    public function preview(
        OutgoingLetter $outgoingLetter,
        OutgoingLetterDocumentVersion $outgoingLetterDocumentVersion,
        PrivateDocumentResponse $response,
    ): StreamedResponse {
        Gate::authorize('view', $outgoingLetter);

        return $response->previewOutgoingLetterDocument($outgoingLetter, $outgoingLetterDocumentVersion);
    }

    public function download(
        OutgoingLetter $outgoingLetter,
        OutgoingLetterDocumentVersion $outgoingLetterDocumentVersion,
        PrivateDocumentResponse $response,
    ): StreamedResponse {
        Gate::authorize('view', $outgoingLetter);

        return $response->downloadOutgoingLetterDocument($outgoingLetter, $outgoingLetterDocumentVersion);
    }
}
