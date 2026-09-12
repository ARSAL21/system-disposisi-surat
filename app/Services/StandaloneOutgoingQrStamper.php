<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\StandaloneOutgoing\StoredStandaloneOutgoingDocument;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Throwable;

final class StandaloneOutgoingQrStamper
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $sourceStorage,
        private readonly StandaloneOutgoingFinalDocumentStorage $finalStorage,
    ) {}

    public function stamp(
        OutgoingLetter $letter,
        StandaloneOutgoingDocumentVersion $source,
        OutgoingLetterTemplateVersion $template,
        string $verificationUrl,
    ): StoredStandaloneOutgoingDocument {
        $source->loadMissing('draft');
        $this->sourceStorage->validate($source->draft, $source);

        $sourceFile = tempnam(sys_get_temp_dir(), 'outgoing-source-');
        $qrFile = tempnam(sys_get_temp_dir(), 'outgoing-qr-');
        $outputFile = tempnam(sys_get_temp_dir(), 'outgoing-stamped-');

        if ($sourceFile === false || $qrFile === false || $outputFile === false) {
            throw DocumentStorageConflict::unavailable();
        }

        $qrPng = $qrFile.'.png';
        $pdfOutput = $outputFile.'.pdf';

        try {
            $this->copySourceToTemporaryFile($source, $sourceFile);
            (new Writer(new GDLibRenderer(512)))->writeFile($verificationUrl, $qrPng);

            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($sourceFile);
            $qrPage = $this->qrPage($template, $pageCount);

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                if (! is_array($size)
                    || ! isset($size['width'], $size['height'])
                    || ! is_numeric($size['width'])
                    || ! is_numeric($size['height'])) {
                    throw StandaloneOutgoingStateConflict::pdfNotStampable();
                }
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                if ($page === $qrPage) {
                    $pdf->Image(
                        $qrPng,
                        (float) $size['width'] * $template->qr_x_ratio,
                        (float) $size['height'] * $template->qr_y_ratio,
                        (float) $size['width'] * $template->qr_width_ratio,
                        (float) $size['height'] * $template->qr_height_ratio,
                        'PNG',
                    );
                }
            }

            $pdf->Output('F', $pdfOutput);

            return $this->finalStorage->storePath($pdfOutput, 'surat-disahkan-qr.pdf', $letter);
        } catch (StandaloneOutgoingStateConflict|DocumentStorageConflict $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw StandaloneOutgoingStateConflict::pdfNotStampable();
        } finally {
            foreach ([$sourceFile, $qrFile, $qrPng, $outputFile, $pdfOutput] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function copySourceToTemporaryFile(StandaloneOutgoingDocumentVersion $source, string $destination): void
    {
        $stream = Storage::disk($source->storage_disk)->readStream($source->storage_path);
        $target = fopen($destination, 'wb');

        if (! is_resource($stream) || ! is_resource($target)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            throw DocumentStorageConflict::unavailable();
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }
    }

    private function qrPage(OutgoingLetterTemplateVersion $template, int $pageCount): int
    {
        $page = $template->qr_page_mode === 'SPECIFIC_PAGE'
            ? $template->qr_page_number
            : $pageCount;

        if (! is_int($page) || $page < 1 || $page > $pageCount) {
            throw StandaloneOutgoingStateConflict::pdfNotStampable();
        }

        return $page;
    }
}
