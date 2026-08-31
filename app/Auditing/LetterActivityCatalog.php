<?php

namespace App\Auditing;

use App\Enums\AuditAction;

final class LetterActivityCatalog
{
    public const string SOURCE_PUBLIC = 'public';

    public const string SOURCE_INTERNAL = 'internal';

    /** @return list<string> */
    public static function actions(): array
    {
        return array_keys(self::actionLabels());
    }

    /** @return list<string> */
    public static function submissionActions(): array
    {
        return [
            AuditAction::SubmissionSubmitted->value,
            AuditAction::SubmissionResubmitted->value,
            AuditAction::SubmissionRevisionRequested->value,
            AuditAction::SubmissionReadyForApproval->value,
            AuditAction::SubmissionReturnedToStaff->value,
            AuditAction::SubmissionRejected->value,
        ];
    }

    /** @return list<string> */
    public static function sources(): array
    {
        return [self::SOURCE_PUBLIC, self::SOURCE_INTERNAL];
    }

    /** @return array<string, string> */
    public static function actionLabels(): array
    {
        return [
            AuditAction::SubmissionSubmitted->value => 'Surat diajukan',
            AuditAction::SubmissionResubmitted->value => 'Surat diajukan kembali',
            AuditAction::SubmissionRevisionRequested->value => 'Perbaikan diminta',
            AuditAction::SubmissionReadyForApproval->value => 'Siap ditinjau Kabag',
            AuditAction::SubmissionReturnedToStaff->value => 'Dikembalikan ke petugas',
            AuditAction::SubmissionRejected->value => 'Surat ditolak',
            AuditAction::LetterRegistered->value => 'Surat diregistrasi',
            AuditAction::DocumentVersionCreated->value => 'Versi dokumen resmi dibuat',
        ];
    }

    /**
     * @param  list<array{value: string, label: string}>  $actors
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function filterOptions(array $actors): array
    {
        $actions = [];

        foreach (self::actionLabels() as $value => $label) {
            $actions[] = compact('value', 'label');
        }

        return [
            'actions' => $actions,
            'sources' => [
                ['value' => self::SOURCE_PUBLIC, 'label' => 'Portal publik'],
                ['value' => self::SOURCE_INTERNAL, 'label' => 'Back-office'],
            ],
            'actors' => $actors,
        ];
    }
}
