<?php

namespace App\LetterResponses;

final readonly class StoredLetterResponseDocument
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $sha256,
    ) {}
}
