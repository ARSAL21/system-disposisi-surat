<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntakeApprovalDocumentController extends Controller
{
    public function show(
        LetterSubmission $submission,
        PrivateDocumentResponse $privateDocumentResponse,
    ): StreamedResponse {
        Gate::authorize('viewApprovalDocument', $submission);
        $document = $submission->document()->first();

        return $privateDocumentResponse->preview($submission, $document);
    }

    public function download(
        LetterSubmission $submission,
        PrivateDocumentResponse $privateDocumentResponse,
    ): StreamedResponse {
        Gate::authorize('downloadApprovalDocument', $submission);
        $document = $submission->document()->first();

        return $privateDocumentResponse->download($submission, $document);
    }
}
