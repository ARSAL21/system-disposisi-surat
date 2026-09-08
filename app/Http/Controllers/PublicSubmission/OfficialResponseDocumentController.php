<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Services\PrivateDocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OfficialResponseDocumentController extends Controller
{
    public function preview(
        LetterSubmission $submission,
        OutgoingLetter $outgoingLetter,
        PrivateDocumentResponse $response,
    ): StreamedResponse {
        return $response->previewOutgoingLetterDocument(
            $outgoingLetter,
            $this->authorizedVersion($submission, $outgoingLetter),
        );
    }

    public function download(
        LetterSubmission $submission,
        OutgoingLetter $outgoingLetter,
        PrivateDocumentResponse $response,
    ): StreamedResponse {
        return $response->downloadOutgoingLetterDocument(
            $outgoingLetter,
            $this->authorizedVersion($submission, $outgoingLetter),
        );
    }

    private function authorizedVersion(
        LetterSubmission $submission,
        OutgoingLetter $outgoingLetter,
    ): OutgoingLetterDocumentVersion {
        Gate::authorize('view', $submission);
        $incomingLetterId = $submission->incomingLetter()->value('id');
        abort_unless($incomingLetterId !== null
            && (int) $outgoingLetter->incoming_letter_id === (int) $incomingLetterId
            && $outgoingLetter->status === OutgoingLetterStatus::Delivered, 404);

        return OutgoingLetterDocumentVersion::query()
            ->where('outgoing_letter_id', $outgoingLetter->getKey())
            ->whereHas('review', fn ($review) => $review
                ->where('decision', OutgoingLetterReviewDecision::Verified->value))
            ->orderByDesc('version_number')
            ->firstOrFail();
    }
}
