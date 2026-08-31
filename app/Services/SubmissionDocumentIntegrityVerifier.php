<?php

namespace App\Services;

use App\Models\SubmissionDocument;

class SubmissionDocumentIntegrityVerifier
{
    public function __construct(
        private readonly DocumentIntegrityVerifier $verifier,
    ) {}

    public function verify(SubmissionDocument $document): void
    {
        $this->verifier->verifyOrFail($document);
    }
}
