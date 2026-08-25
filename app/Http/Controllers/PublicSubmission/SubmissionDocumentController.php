<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Actions\ReplaceSubmissionDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSubmission\ReplaceSubmissionDocumentRequest;
use App\Http\Resources\LetterSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

        $resource = new LetterSubmissionResource($submission->refresh()->load('document'));

        if ($request->expectsJson()) {
            return $resource;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dokumen PDF berhasil disimpan secara privat.'),
        ]);

        return to_route('public.submissions.edit', $submission);
    }

    public function download(LetterSubmission $submission): StreamedResponse
    {
        Gate::authorize('downloadDocument', $submission);

        $document = $submission->document()->firstOrFail();

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            $document->original_filename,
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
