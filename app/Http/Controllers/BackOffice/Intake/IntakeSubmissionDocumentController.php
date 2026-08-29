<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntakeSubmissionDocumentController extends Controller
{
    public function show(LetterSubmission $submission): StreamedResponse
    {
        Gate::authorize('downloadIntakeDocument', $submission);
        $document = $submission->document()->firstOrFail();

        return Storage::disk($document->storage_disk)->response(
            $document->storage_path,
            $document->original_filename,
            $this->securityHeaders(),
        );
    }

    public function download(LetterSubmission $submission): StreamedResponse
    {
        Gate::authorize('downloadIntakeDocument', $submission);
        $document = $submission->document()->firstOrFail();

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            $document->original_filename,
            $this->securityHeaders(),
        );
    }

    /** @return array<string, string> */
    private function securityHeaders(): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'; sandbox",
        ];
    }
}
