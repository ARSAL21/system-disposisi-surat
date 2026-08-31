<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Actions\ReplaceSubmissionDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSubmission\ReplaceSubmissionDocumentRequest;
use App\Http\Resources\LetterSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use App\Services\PrivateDocumentResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionDocumentController extends Controller
{
    public function replace(
        ReplaceSubmissionDocumentRequest $request,
        LetterSubmission $submission,
        ReplaceSubmissionDocument $replaceSubmissionDocument,
    ): LetterSubmissionResource|RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $replaceSubmissionDocument->execute($user, $submission, $request->file('document'));

        $resource = new LetterSubmissionResource($submission->refresh()->load(['document', 'latestReview']));

        if ($request->expectsJson()) {
            return $resource;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dokumen PDF berhasil disimpan secara privat.'),
        ]);

        return to_route('public.submissions.edit', $submission);
    }

    public function download(
        LetterSubmission $submission,
        PrivateDocumentResponse $privateDocumentResponse,
    ): StreamedResponse {
        Gate::authorize('downloadDocument', $submission);

        $document = $submission->document()->first();

        return $privateDocumentResponse->download($submission, $document);
    }
}
