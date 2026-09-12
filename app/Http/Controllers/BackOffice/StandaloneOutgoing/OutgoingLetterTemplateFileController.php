<?php

namespace App\Http\Controllers\BackOffice\StandaloneOutgoing;

use App\Exceptions\DocumentStorageConflict;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Services\OutgoingLetterTemplateStorage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OutgoingLetterTemplateFileController extends Controller
{
    public function download(
        OutgoingLetterTemplate $outgoingLetterTemplate,
        OutgoingLetterTemplateVersion $outgoingLetterTemplateVersion,
        OutgoingLetterTemplateStorage $storage,
    ): StreamedResponse {
        Gate::authorize('view', $outgoingLetterTemplate);
        abort_unless((int) $outgoingLetterTemplateVersion->outgoing_letter_template_id === (int) $outgoingLetterTemplate->getKey(), 404);
        $storage->validate($outgoingLetterTemplate, $outgoingLetterTemplateVersion);

        try {
            $stream = Storage::disk($outgoingLetterTemplateVersion->storage_disk)->readStream($outgoingLetterTemplateVersion->storage_path);
        } catch (\Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }
        if (! is_resource($stream)) {
            throw DocumentStorageConflict::unavailable();
        }

        $filename = $outgoingLetterTemplateVersion->original_filename ?: 'template-'.$outgoingLetterTemplate->public_id.'.docx';
        $disposition = HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename, 'template.docx');

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
