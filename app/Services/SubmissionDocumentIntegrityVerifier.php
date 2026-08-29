<?php

namespace App\Services;

use App\Exceptions\DocumentIntegrityConflict;
use App\Models\SubmissionDocument;
use Illuminate\Support\Facades\Storage;

class SubmissionDocumentIntegrityVerifier
{
    public function verify(SubmissionDocument $document): void
    {
        $stream = Storage::disk($document->storage_disk)
            ->readStream($document->storage_path);

        if (! is_resource($stream)) {
            throw DocumentIntegrityConflict::unavailable();
        }

        try {
            $context = hash_init('sha256');
            $bytesRead = hash_update_stream($context, $stream);
            $actualHash = hash_final($context);
        } finally {
            fclose($stream);
        }

        if ($bytesRead !== $document->size_bytes
            || ! hash_equals(strtolower($document->sha256), strtolower($actualHash))) {
            throw DocumentIntegrityConflict::fingerprintMismatch();
        }
    }
}
