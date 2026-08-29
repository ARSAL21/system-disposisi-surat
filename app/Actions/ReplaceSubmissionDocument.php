<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\SubmissionStateConflict;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReplaceSubmissionDocument
{
    private const DISK = 'submission-documents';

    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, LetterSubmission $submission, UploadedFile $file): SubmissionDocument
    {
        $sha256 = hash_file('sha256', $file->getRealPath());

        if ($sha256 === false) {
            throw new RuntimeException('Unable to calculate the document fingerprint.');
        }

        $path = Storage::disk(self::DISK)->putFileAs(
            $submission->public_id,
            $file,
            Str::uuid()->toString().'.pdf',
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store the submission document.');
        }

        $oldPath = null;

        try {
            $document = DB::transaction(function () use ($actor, $submission, $file, $sha256, $path, &$oldPath): SubmissionDocument {
                $lockedSubmission = LetterSubmission::query()
                    ->lockForUpdate()
                    ->whereKey($submission->getKey())
                    ->firstOrFail();

                if (! $lockedSubmission->isPubliclyEditable()) {
                    throw SubmissionStateConflict::expectedPubliclyEditable($lockedSubmission->status);
                }

                $document = SubmissionDocument::query()
                    ->where('letter_submission_id', $lockedSubmission->getKey())
                    ->first();

                $oldValues = null;

                if ($document instanceof SubmissionDocument) {
                    $oldPath = $document->storage_path;
                    $oldValues = [
                        'sha256' => $document->sha256,
                        'size_bytes' => $document->size_bytes,
                    ];
                } else {
                    $document = new SubmissionDocument;
                    $document->letter_submission_id = $lockedSubmission->getKey();
                }

                $document->storage_disk = self::DISK;
                $document->storage_path = $path;
                $document->original_filename = $this->safeOriginalFilename($file);
                $document->mime_type = $file->getMimeType() ?: 'application/pdf';
                $document->size_bytes = (int) $file->getSize();
                $document->sha256 = $sha256;
                $document->uploaded_by_user_id = $actor->getKey();
                $document->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::SubmissionDocumentReplaced,
                    subjectType: 'letter_submission',
                    subjectId: $lockedSubmission->getKey(),
                    oldValues: $oldValues,
                    newValues: [
                        'sha256' => $document->sha256,
                        'size_bytes' => $document->size_bytes,
                    ],
                    metadata: ['public_id' => $lockedSubmission->public_id],
                );

                return $document;
            }, attempts: 3);
        } catch (Throwable $exception) {
            try {
                Storage::disk(self::DISK)->delete($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        if (is_string($oldPath) && $oldPath !== $path) {
            try {
                Storage::disk(self::DISK)->delete($oldPath);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $document;
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document.pdf';

        return Str::substr($filename, 0, 255);
    }
}
