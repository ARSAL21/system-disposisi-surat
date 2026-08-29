<?php

namespace App\Services;

use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\SubmissionDocument;

class InitialLetterDocumentCreator
{
    public function execute(
        IncomingLetter $incomingLetter,
        SubmissionDocument $sourceDocument,
    ): LetterDocument {
        $document = new LetterDocument;
        $document->incoming_letter_id = $incomingLetter->getKey();
        $document->source_submission_document_id = $sourceDocument->getKey();
        $document->version_number = 1;
        $document->replaces_document_id = null;
        $document->storage_disk = $sourceDocument->storage_disk;
        $document->storage_path = $sourceDocument->storage_path;
        $document->original_filename = $sourceDocument->original_filename;
        $document->mime_type = $sourceDocument->mime_type;
        $document->size_bytes = $sourceDocument->size_bytes;
        $document->sha256 = strtolower($sourceDocument->sha256);
        $document->correction_reason = null;
        $document->uploaded_by_user_id = $sourceDocument->uploaded_by_user_id;
        $document->save();

        return $document;
    }
}
