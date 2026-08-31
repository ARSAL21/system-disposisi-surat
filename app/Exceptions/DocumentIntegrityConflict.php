<?php

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class DocumentIntegrityConflict extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('Dokumen sumber tidak tersedia atau tidak dapat dibaca pada penyimpanan privat.');
    }

    public static function fingerprintMismatch(): self
    {
        return new self('Integritas dokumen tidak dapat diverifikasi (sidik jari atau ukuran berkas tidak cocok).');
    }

    public static function invalidDisk(): self
    {
        return new self('Penyimpanan dokumen tidak valid.');
    }

    public static function invalidPath(): self
    {
        return new self('Lokasi dokumen tidak valid.');
    }

    public static function invalidMetadata(): self
    {
        return new self('Metadata integritas dokumen pada basis data tidak valid.');
    }

    public function render(): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return response($this->getMessage(), Response::HTTP_CONFLICT, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
