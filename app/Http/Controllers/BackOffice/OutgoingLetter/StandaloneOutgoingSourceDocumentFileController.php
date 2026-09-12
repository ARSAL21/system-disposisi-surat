<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StandaloneOutgoingSourceDocumentFileController extends Controller
{
    public function preview(OutgoingLetter $outgoingLetter, StandaloneOutgoingDocumentVersion $standaloneDocument, PrivateDocumentResponse $response): StreamedResponse
    {
        return $this->respond($outgoingLetter, $standaloneDocument, $response, false);
    }

    public function download(OutgoingLetter $outgoingLetter, StandaloneOutgoingDocumentVersion $standaloneDocument, PrivateDocumentResponse $response): StreamedResponse
    {
        return $this->respond($outgoingLetter, $standaloneDocument, $response, true);
    }

    private function respond(OutgoingLetter $letter, StandaloneOutgoingDocumentVersion $version, PrivateDocumentResponse $response, bool $download): StreamedResponse
    {
        Gate::authorize('view', $letter);
        $draft = $letter->standaloneDraft;
        abort_unless($letter->origin->value === 'STANDALONE'
            && $draft !== null
            && (int) $version->standalone_outgoing_draft_id === (int) $draft->getKey(), 404);

        return $download
            ? $response->downloadStandaloneOutgoingSourceDocument($draft, $version)
            : $response->previewStandaloneOutgoingSourceDocument($draft, $version);
    }
}
