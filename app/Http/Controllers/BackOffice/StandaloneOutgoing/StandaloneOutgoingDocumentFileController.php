<?php

namespace App\Http\Controllers\BackOffice\StandaloneOutgoing;

use App\Exceptions\DocumentStorageConflict;
use App\Http\Controllers\Controller;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Services\StandaloneOutgoingDocumentStorage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StandaloneOutgoingDocumentFileController extends Controller
{
    public function preview(StandaloneOutgoingDraft $standaloneOutgoingDraft, StandaloneOutgoingDocumentVersion $documentVersion, StandaloneOutgoingDocumentStorage $storage): StreamedResponse
    {
        return $this->respond($standaloneOutgoingDraft, $documentVersion, $storage, false);
    }

    public function download(StandaloneOutgoingDraft $standaloneOutgoingDraft, StandaloneOutgoingDocumentVersion $documentVersion, StandaloneOutgoingDocumentStorage $storage): StreamedResponse
    {
        return $this->respond($standaloneOutgoingDraft, $documentVersion, $storage, true);
    }

    private function respond(StandaloneOutgoingDraft $draft, StandaloneOutgoingDocumentVersion $version, StandaloneOutgoingDocumentStorage $storage, bool $download): StreamedResponse
    {
        Gate::authorize('view', $draft);
        abort_unless((int) $version->standalone_outgoing_draft_id === (int) $draft->getKey(), 404);
        $storage->validate($draft, $version);

        try {
            $stream = Storage::disk($version->storage_disk)->readStream($version->storage_path);
        } catch (\Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }
        if (! is_resource($stream)) {
            throw DocumentStorageConflict::unavailable();
        }

        $filename = $version->original_filename ?: 'konsep-'.$draft->public_id.'.pdf';
        $disposition = HeaderUtils::makeDisposition(
            $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $filename,
            'konsep-surat.pdf',
        );

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'; sandbox",
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
    }
}
