<?php

namespace App\Auditing;

use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\SubmissionStatus;

final class LetterActivityPayloadSanitizer
{
    private const array SAFE_CHANGE_FIELDS = [
        'status' => 'status',
        'submitted_at' => 'waktu_pengajuan',
        'submission_status' => 'status_pengajuan',
        'letter_status' => 'status_surat',
        'route_status' => 'status_routing',
        'agenda_number' => 'nomor_agenda',
        'agenda_year' => 'tahun_agenda',
        'version_number' => 'versi_dokumen',
    ];

    public function __construct(
        private readonly PrivilegeAuditPayloadSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $changes
     * @return array<string, string|int|bool|null>|null
     */
    public function changes(?array $changes): ?array
    {
        if ($changes === null) {
            return null;
        }

        $safe = [];

        foreach (self::SAFE_CHANGE_FIELDS as $source => $target) {
            if (! array_key_exists($source, $changes)) {
                continue;
            }

            $value = $this->changeValue($changes[$source]);

            if ($value !== null || $changes[$source] === null) {
                $safe[$target] = $value;
            }
        }

        return $safe === [] ? null : $safe;
    }

    public function identifier(mixed $value, int $limit): ?string
    {
        return $this->sanitizer->identifier($value, $limit);
    }

    public function text(?string $value, int $limit): ?string
    {
        return $this->sanitizer->text($value, $limit);
    }

    public function ipAddress(?string $value): ?string
    {
        return $this->sanitizer->ipAddress($value);
    }

    public function userAgent(?string $value): ?string
    {
        return $this->sanitizer->userAgent($value);
    }

    private function changeValue(mixed $value): string|int|bool|null
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return $this->statusLabel($value)
            ?? $this->sanitizer->text($value, 500);
    }

    private function statusLabel(string $value): ?string
    {
        return match ($value) {
            SubmissionStatus::Draft->value => 'Draf',
            SubmissionStatus::Submitted->value => 'Diajukan',
            SubmissionStatus::RevisionRequired->value => 'Menunggu perbaikan pemohon',
            SubmissionStatus::ReadyForApproval->value => 'Siap ditinjau Kabag',
            SubmissionStatus::InternalRevisionRequired->value => 'Perlu diperiksa ulang',
            SubmissionStatus::Registered->value => 'Terdaftar',
            SubmissionStatus::Rejected->value => 'Ditolak',
            IncomingLetterStatus::Routed->value => 'Telah diarahkan',
            IncomingLetterStatus::InProgress->value => 'Dalam proses',
            IncomingLetterStatus::Completed->value => 'Selesai',
            LetterRouteStatus::Pending->value => 'Menunggu disposisi pimpinan',
            default => null,
        };
    }
}
