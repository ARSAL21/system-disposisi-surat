<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\ExpertConsultations\ExpertConsultationDocumentStorage;
use App\Models\ExpertConsultation;
use App\Models\ExpertConsultationDocument;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterSubmission;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\SubmissionDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PrivateDocumentResponse
{
    public function __construct(
        private readonly DocumentStorageGuard $storageGuard = new DocumentStorageGuard,
    ) {}

    /**
     * Build an inline PDF preview response.
     */
    public function preview(LetterSubmission $submission, ?SubmissionDocument $document): StreamedResponse
    {
        return $this->buildResponse($submission, $document, asDownload: false);
    }

    /**
     * Build an attachment PDF download response.
     */
    public function download(LetterSubmission $submission, ?SubmissionDocument $document): StreamedResponse
    {
        return $this->buildResponse($submission, $document, asDownload: true);
    }

    public function previewLetterDocument(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
    ): StreamedResponse {
        return $this->buildLetterDocumentResponse($incomingLetter, $document, asDownload: false);
    }

    public function downloadLetterDocument(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
    ): StreamedResponse {
        return $this->buildLetterDocumentResponse($incomingLetter, $document, asDownload: true);
    }

    public function previewLetterResponseDocument(
        LetterResponseDossier $dossier,
        LetterResponseDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildLetterResponseDocument($dossier, $version, false);
    }

    public function downloadLetterResponseDocument(
        LetterResponseDossier $dossier,
        LetterResponseDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildLetterResponseDocument($dossier, $version, true);
    }

    public function previewExpertConsultationDocument(ExpertConsultation $consultation, ExpertConsultationDocument $document): StreamedResponse
    {
        return $this->buildExpertConsultationDocument($consultation, $document, false);
    }

    public function downloadExpertConsultationDocument(ExpertConsultation $consultation, ExpertConsultationDocument $document): StreamedResponse
    {
        return $this->buildExpertConsultationDocument($consultation, $document, true);
    }

    public function previewOutgoingLetterDocument(
        OutgoingLetter $letter,
        OutgoingLetterDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildOutgoingLetterDocument($letter, $version, false);
    }

    public function downloadOutgoingLetterDocument(
        OutgoingLetter $letter,
        OutgoingLetterDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildOutgoingLetterDocument($letter, $version, true);
    }

    public function previewStandaloneOutgoingSourceDocument(
        StandaloneOutgoingDraft $draft,
        StandaloneOutgoingDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildStandaloneOutgoingSourceDocument($draft, $version, false);
    }

    public function downloadStandaloneOutgoingSourceDocument(
        StandaloneOutgoingDraft $draft,
        StandaloneOutgoingDocumentVersion $version,
    ): StreamedResponse {
        return $this->buildStandaloneOutgoingSourceDocument($draft, $version, true);
    }

    private function buildOutgoingLetterDocument(
        OutgoingLetter $letter,
        OutgoingLetterDocumentVersion $version,
        bool $asDownload,
    ): StreamedResponse {
        if ($letter->origin->value === 'STANDALONE') {
            app(StandaloneOutgoingFinalDocumentStorage::class)->validate($letter, $version);
        } else {
            app(OutgoingLetterDocumentStorage::class)->validate($letter, $version);
        }

        return $this->buildStorageResponse(
            disk: $version->storage_disk,
            path: $version->storage_path,
            originalFilename: $version->original_filename,
            fallbackId: 'surat-keluar-'.$letter->public_id,
            asDownload: $asDownload,
        );
    }

    private function buildStandaloneOutgoingSourceDocument(
        StandaloneOutgoingDraft $draft,
        StandaloneOutgoingDocumentVersion $version,
        bool $asDownload,
    ): StreamedResponse {
        app(StandaloneOutgoingDocumentStorage::class)->validate($draft, $version);

        return $this->buildStorageResponse(
            disk: $version->storage_disk,
            path: $version->storage_path,
            originalFilename: $version->original_filename,
            fallbackId: 'konsep-'.$draft->public_id,
            asDownload: $asDownload,
        );
    }

    private function buildLetterResponseDocument(
        LetterResponseDossier $dossier,
        LetterResponseDocumentVersion $version,
        bool $asDownload,
    ): StreamedResponse {
        app(LetterResponseDocumentStorage::class)->validate($dossier, $version);

        return $this->buildStorageResponse(
            disk: $version->storage_disk,
            path: $version->storage_path,
            originalFilename: $version->original_filename,
            fallbackId: 'balasan-'.$dossier->public_id,
            asDownload: $asDownload,
        );
    }

    private function buildExpertConsultationDocument(
        ExpertConsultation $consultation,
        ExpertConsultationDocument $document,
        bool $asDownload,
    ): StreamedResponse {
        app(ExpertConsultationDocumentStorage::class)->validate($consultation, $document);

        return $this->buildStorageResponse(
            disk: $document->storage_disk,
            path: $document->storage_path,
            originalFilename: $document->original_filename,
            fallbackId: 'telaah-'.$consultation->getKey(),
            asDownload: $asDownload,
        );
    }

    private function buildResponse(
        LetterSubmission $submission,
        ?SubmissionDocument $document,
        bool $asDownload,
    ): StreamedResponse {
        if ($document === null) {
            throw DocumentStorageConflict::unavailable();
        }

        $this->storageGuard->validateSubmissionDocument($document, $submission);

        return $this->buildStorageResponse(
            disk: $document->storage_disk,
            path: $document->storage_path,
            originalFilename: $document->original_filename,
            fallbackId: $submission->public_id,
            asDownload: $asDownload,
        );
    }

    private function buildLetterDocumentResponse(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
        bool $asDownload,
    ): StreamedResponse {
        $this->storageGuard->validateOfficialLetterDocument($incomingLetter, $document);

        return $this->buildStorageResponse(
            disk: $document->storage_disk,
            path: $document->storage_path,
            originalFilename: $document->original_filename,
            fallbackId: 'agenda-'.$incomingLetter->agenda_number,
            asDownload: $asDownload,
        );
    }

    private function buildStorageResponse(
        string $disk,
        string $path,
        ?string $originalFilename,
        string $fallbackId,
        bool $asDownload,
    ): StreamedResponse {
        $stream = $this->openFileStream($disk, $path);

        $dispositionType = $asDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $dispositionHeader = $this->buildContentDisposition(
            $dispositionType,
            $originalFilename,
            $fallbackId,
        );

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $dispositionHeader,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'; sandbox",
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        $stat = is_resource($stream) ? fstat($stream) : null;
        if (is_array($stat) && $stat['size'] >= 0) {
            $headers['Content-Length'] = (string) $stat['size'];
        }

        return new StreamedResponse(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, $headers);
    }

    /**
     * Open file stream once on the private disk.
     *
     * @return resource
     */
    private function openFileStream(string $disk, string $path)
    {
        try {
            $diskStorage = Storage::disk($disk);
            if (! $diskStorage->exists($path)) {
                throw DocumentStorageConflict::unavailable();
            }

            $stream = $diskStorage->readStream($path);
            if (! is_resource($stream)) {
                throw DocumentStorageConflict::unavailable();
            }

            return $stream;
        } catch (DocumentStorageConflict $e) {
            throw $e;
        } catch (Throwable) {
            throw DocumentStorageConflict::unavailable();
        }
    }

    private function buildContentDisposition(string $dispositionType, ?string $originalFilename, string $fallbackId): string
    {
        $rawName = basename(str_replace('\\', '/', (string) $originalFilename));
        $cleaned = (string) preg_replace('/[\x00-\x1F\x7F"\r\n\t]/u', '', $rawName);
        $cleaned = trim($cleaned);

        if (str_ends_with(strtolower($cleaned), '.pdf')) {
            $stem = substr($cleaned, 0, -4);
        } else {
            $stem = $cleaned;
        }

        $stem = trim($stem);
        if ($stem === '') {
            $stem = $fallbackId;
        }

        // Truncate stem to ensure stem + '.pdf' is at most 200 characters
        $truncatedStem = Str::substr($stem, 0, 196);
        $unicodeFilename = $truncatedStem.'.pdf';

        // Generate clean ASCII fallback
        $asciiStem = Str::ascii($truncatedStem);
        $asciiStem = (string) preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $asciiStem);
        $asciiStem = trim($asciiStem, '._- ');
        if ($asciiStem === '') {
            $asciiStem = $fallbackId;
        }
        $asciiFallback = Str::substr($asciiStem, 0, 196).'.pdf';

        return HeaderUtils::makeDisposition($dispositionType, $unicodeFilename, $asciiFallback);
    }
}
