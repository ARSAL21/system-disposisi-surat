<?php

namespace App\Console\Commands;

use App\Models\LetterDocument;
use App\Models\SubmissionDocument;
use App\Services\DocumentIntegrityResult;
use App\Services\DocumentIntegrityVerifier;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class VerifyDocumentIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:verify-integrity
                            {--submissions : Scan only online submission documents}
                            {--letters : Scan only official letter documents}
                            {--all : Scan both submission and letter documents}
                            {--fail-fast : Stop immediately on first verification failure}
                            {--limit= : Maximum number of documents to inspect (positive integer)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify cryptographic SHA-256 fingerprint and size_bytes of private documents.';

    public function handle(DocumentIntegrityVerifier $verifier): int
    {
        $scanAll = (bool) $this->option('all');
        $scanSubmissions = (bool) $this->option('submissions');
        $scanLetters = (bool) $this->option('letters');
        $failFast = (bool) $this->option('fail-fast');
        $limitOption = $this->option('limit');

        $limit = null;
        if ($limitOption !== null) {
            if (! is_numeric($limitOption) || (int) $limitOption <= 0) {
                $this->error('The --limit option must be a positive integer greater than 0.');

                return self::FAILURE;
            }
            $limit = (int) $limitOption;
        }

        if ($scanAll || (! $scanSubmissions && ! $scanLetters)) {
            $scanSubmissions = true;
            $scanLetters = true;
        }

        $this->info('Starting document cryptographic integrity scan...');

        $passed = 0;
        $failed = 0;
        $scannedCount = 0;
        $failedRows = $this->emptyFailedRows();

        if ($scanSubmissions) {
            $query = SubmissionDocument::query()->with('submission')->orderBy('id');
            foreach ($query->lazyById(100) as $submissionDoc) {
                if ($limit !== null && $scannedCount >= $limit) {
                    break;
                }

                $scannedCount++;
                $result = $verifier->inspect($submissionDoc);
                $this->processResult('SubmissionDocument', $submissionDoc, $result, $passed, $failed, $failedRows);

                if ($failFast && ! $result->isValid()) {
                    $this->error(sprintf(
                        'Fail-fast triggered on SubmissionDocument #%d: %s',
                        $submissionDoc->getKey(),
                        $this->sanitizeTerminalString($result->errorMessage),
                    ));

                    return $this->renderSummaryAndExit($scannedCount, $passed, $failed, $failedRows);
                }
            }
        }

        if ($scanLetters && ($limit === null || $scannedCount < $limit)) {
            $query = LetterDocument::query()->orderBy('id');
            foreach ($query->lazyById(100) as $letterDoc) {
                if ($limit !== null && $scannedCount >= $limit) {
                    break;
                }

                $scannedCount++;
                $result = $verifier->inspect($letterDoc);
                $this->processResult('LetterDocument', $letterDoc, $result, $passed, $failed, $failedRows);

                if ($failFast && ! $result->isValid()) {
                    $this->error(sprintf(
                        'Fail-fast triggered on LetterDocument #%d: %s',
                        $letterDoc->getKey(),
                        $this->sanitizeTerminalString($result->errorMessage),
                    ));

                    return $this->renderSummaryAndExit($scannedCount, $passed, $failed, $failedRows);
                }
            }
        }

        return $this->renderSummaryAndExit($scannedCount, $passed, $failed, $failedRows);
    }

    /**
     * @param  array<int, array<string, string|int>>  $failedRows
     *
     * @param-out array<int, array<string, string|int>> $failedRows
     */
    private function processResult(
        string $type,
        SubmissionDocument|LetterDocument $model,
        DocumentIntegrityResult $result,
        int &$passed,
        int &$failed,
        array &$failedRows,
    ): void {
        if ($result->isValid()) {
            $passed++;

            return;
        }

        $failed++;

        $statusText = match ($result->status) {
            DocumentIntegrityResult::STATUS_HASH_MISMATCH => '<error>HASH MISMATCH</error>',
            DocumentIntegrityResult::STATUS_SIZE_MISMATCH => '<error>SIZE MISMATCH</error>',
            DocumentIntegrityResult::STATUS_INVALID_METADATA => '<error>INVALID METADATA</error>',
            DocumentIntegrityResult::STATUS_FILE_UNAVAILABLE => '<error>MISSING/UNREADABLE</error>',
            DocumentIntegrityResult::STATUS_INVALID_PATH => '<error>INVALID PATH</error>',
            DocumentIntegrityResult::STATUS_INVALID_DISK => '<error>INVALID DISK</error>',
            default => '<error>FAILED</error>',
        };

        $expectedHash = substr($model->sha256, 0, 12).'...';
        $actualHash = $result->actualHash !== null ? substr($result->actualHash, 0, 12).'...' : 'N/A';

        $failedRows[] = [
            'id' => (int) $model->getKey(),
            'type' => $type,
            'disk' => $this->sanitizeTerminalString($model->storage_disk),
            'path' => $this->sanitizeTerminalString($model->storage_path),
            'expected_hash' => $this->sanitizeTerminalString($expectedHash),
            'actual_hash' => $this->sanitizeTerminalString($actualHash),
            'expected_bytes' => (string) $model->size_bytes,
            'actual_bytes' => (string) ($result->actualBytes ?? 'N/A'),
            'status' => $statusText,
        ];
    }

    /**
     * @param  array<int, array<string, string|int>>  $failedRows
     */
    private function renderSummaryAndExit(int $total, int $passed, int $failed, array $failedRows): int
    {
        if ($total === 0) {
            $this->warn('No documents found matching the criteria.');

            return self::SUCCESS;
        }

        if (count($failedRows) > 0) {
            $this->newLine();
            $this->error('Failed / Corrupted Document Findings:');
            $this->table(
                ['ID', 'Type', 'Disk', 'Path', 'Exp Hash', 'Act Hash', 'Exp Bytes', 'Act Bytes', 'Status'],
                $failedRows,
            );
        }

        $this->newLine();
        $this->line("Total Scanned: <comment>{$total}</comment>");
        $this->line("Passed: <info>{$passed}</info>");
        if ($failed > 0) {
            $this->line("Failed: <error>{$failed}</error>");
            $this->error('Integrity check failed. Some documents are corrupted, missing, or have invalid signatures.');

            return self::FAILURE;
        }

        $this->info('All scanned documents successfully verified.');

        return self::SUCCESS;
    }

    private function sanitizeTerminalString(?string $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        $cleaned = (string) preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $value);

        return OutputFormatter::escape(trim($cleaned));
    }

    /** @return array<int, array<string, string|int>> */
    private function emptyFailedRows(): array
    {
        return [];
    }
}
