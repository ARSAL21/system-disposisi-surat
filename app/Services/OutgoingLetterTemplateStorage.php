<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\StandaloneOutgoing\StoredOutgoingTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

final class OutgoingLetterTemplateStorage
{
    public const string DISK = 'outgoing-letter-templates';

    public function store(UploadedFile $file, int $unitId): StoredOutgoingTemplate
    {
        $realPath = $file->getRealPath();
        $hash = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();

        if ($hash === false || $size === false || $size < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        if (! $this->isSafeDocx($file)) {
            throw ValidationException::withMessages([
                'document' => 'Template harus berupa DOCX yang valid, tanpa macro, objek tersemat, atau tautan eksternal.',
            ]);
        }

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                'units/'.$unitId,
                $file,
                Str::uuid()->toString().'.docx',
            );
        } catch (Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }

        if ($path === false) {
            throw DocumentStorageConflict::unavailable();
        }

        return new StoredOutgoingTemplate(
            disk: self::DISK,
            path: $path,
            originalFilename: $this->safeFilename($file, 'template.docx'),
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            sizeBytes: (int) $size,
            sha256: strtolower($hash),
        );
    }

    public function delete(StoredOutgoingTemplate $template): void
    {
        try {
            Storage::disk($template->disk)->delete($template->path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function validate(OutgoingLetterTemplate $template, OutgoingLetterTemplateVersion $version): void
    {
        if ((int) $version->outgoing_letter_template_id !== (int) $template->getKey()
            || $version->storage_disk !== self::DISK
            || $version->mime_type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $path = $version->storage_path;
        if (! str_starts_with($path, 'units/'.$template->organizational_unit_id.'/')
            || ! str_ends_with(strtolower($path), '.docx')
            || str_contains($path, '..')
            || ! preg_match('#^units/\d+/[0-9a-f-]{36}\.docx$#', $path)) {
            throw DocumentStorageConflict::invalidPath();
        }

        if (! preg_match('/^[a-f0-9]{64}$/', $version->sha256) || $version->size_bytes < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        try {
            $disk = Storage::disk(self::DISK);
            if (! $disk->exists($path)) {
                throw DocumentStorageConflict::unavailable();
            }

            if ($disk->size($path) !== $version->size_bytes) {
                throw DocumentStorageConflict::invalidMetadata();
            }
        } catch (DocumentStorageConflict $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }
    }

    private function isSafeDocx(UploadedFile $file): bool
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'docx') {
            return false;
        }

        $path = $file->getRealPath();
        if (! is_string($path)) {
            return false;
        }

        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            return false;
        }

        try {
            if ($archive->locateName('[Content_Types].xml') === false
                || $archive->locateName('word/document.xml') === false) {
                return false;
            }

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = strtolower((string) $archive->getNameIndex($index));
                if (str_ends_with($name, 'vbaproject.bin') || str_contains($name, 'embeddings/')) {
                    return false;
                }

                if (str_ends_with($name, '.rels')) {
                    $relationships = $archive->getFromIndex($index);
                    if (is_string($relationships) && str_contains($relationships, 'TargetMode="External"')) {
                        return false;
                    }
                }
            }

            return true;
        } finally {
            $archive->close();
        }
    }

    private function safeFilename(UploadedFile $file, string $fallback): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: $fallback;

        return Str::substr($filename, 0, 255);
    }
}
