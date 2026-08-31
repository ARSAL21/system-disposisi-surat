<?php

namespace App\Services;

final class DocumentIntegrityResult
{
    public const STATUS_MATCH = 'MATCH';

    public const STATUS_HASH_MISMATCH = 'HASH_MISMATCH';

    public const STATUS_SIZE_MISMATCH = 'SIZE_MISMATCH';

    public const STATUS_FILE_UNAVAILABLE = 'FILE_UNAVAILABLE';

    public const STATUS_INVALID_PATH = 'INVALID_PATH';

    public const STATUS_INVALID_DISK = 'INVALID_DISK';

    public const STATUS_INVALID_METADATA = 'INVALID_METADATA';

    public function __construct(
        public readonly string $status,
        public readonly ?string $expectedHash = null,
        public readonly ?string $actualHash = null,
        public readonly ?int $expectedBytes = null,
        public readonly ?int $actualBytes = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function match(string $hash, int $bytes): self
    {
        return new self(
            status: self::STATUS_MATCH,
            expectedHash: strtolower($hash),
            actualHash: strtolower($hash),
            expectedBytes: $bytes,
            actualBytes: $bytes,
        );
    }

    public static function hashMismatch(
        string $expectedHash,
        string $actualHash,
        int $expectedBytes,
        int $actualBytes,
    ): self {
        return new self(
            status: self::STATUS_HASH_MISMATCH,
            expectedHash: strtolower($expectedHash),
            actualHash: strtolower($actualHash),
            expectedBytes: $expectedBytes,
            actualBytes: $actualBytes,
            errorMessage: 'Cryptographic SHA-256 fingerprint does not match the database record.',
        );
    }

    public static function sizeMismatch(
        string $expectedHash,
        string $actualHash,
        int $expectedBytes,
        int $actualBytes,
    ): self {
        return new self(
            status: self::STATUS_SIZE_MISMATCH,
            expectedHash: strtolower($expectedHash),
            actualHash: strtolower($actualHash),
            expectedBytes: $expectedBytes,
            actualBytes: $actualBytes,
            errorMessage: 'Physical file size in bytes does not match the database record.',
        );
    }

    public static function unavailable(string $message = 'Physical file is missing or unreadable on private storage.'): self
    {
        return new self(
            status: self::STATUS_FILE_UNAVAILABLE,
            errorMessage: $message,
        );
    }

    public static function invalidPath(string $message = 'Storage path contains invalid characters, directory traversal, or wrong extension.'): self
    {
        return new self(
            status: self::STATUS_INVALID_PATH,
            errorMessage: $message,
        );
    }

    public static function invalidDisk(string $message = 'Storage disk is not an allowed private storage disk.'): self
    {
        return new self(
            status: self::STATUS_INVALID_DISK,
            errorMessage: $message,
        );
    }

    public static function invalidMetadata(string $message = 'Database document metadata is corrupt or violates storage invariants.'): self
    {
        return new self(
            status: self::STATUS_INVALID_METADATA,
            errorMessage: $message,
        );
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_MATCH;
    }
}
