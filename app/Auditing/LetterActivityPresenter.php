<?php

namespace App\Auditing;

use App\Enums\LetterActivityVisibility;
use App\Models\AuditLog;

final class LetterActivityPresenter
{
    public function __construct(
        private readonly LetterActivityPayloadSanitizer $sanitizer,
    ) {}

    /**
     * @param  array{target: array<string, mixed>, document: array<string, mixed>|null}|null  $resolved
     * @return array<string, mixed>
     */
    public function present(
        AuditLog $audit,
        LetterActivityVisibility $visibility,
        ?array $resolved,
    ): array {
        $showsDetails = $visibility === LetterActivityVisibility::Details;

        return [
            'id' => $audit->getKey(),
            'action' => $audit->action,
            'actor' => $this->actor($audit, $showsDetails),
            'target' => $showsDetails
                ? $this->target($resolved['target'] ?? null)
                : null,
            'before' => $showsDetails
                ? $this->sanitizer->changes($audit->old_values)
                : null,
            'after' => $showsDetails
                ? $this->sanitizer->changes($audit->new_values)
                : null,
            'document' => $showsDetails
                ? $this->document($resolved['document'] ?? null)
                : null,
            'request_id' => $showsDetails
                ? $this->sanitizer->identifier($audit->request_id, 64)
                : null,
            'ip_address' => $showsDetails
                ? $this->sanitizer->ipAddress($audit->ip_address)
                : null,
            'user_agent' => $showsDetails
                ? $this->sanitizer->userAgent($audit->user_agent)
                : null,
            'occurred_at' => $audit->created_at->toISOString(),
        ];
    }

    /** @return array{kind: string, id: int|null, name: string, position: string|null, unit: string|null} */
    private function actor(AuditLog $audit, bool $showsDetails): array
    {
        $actor = $audit->actor;

        if ($actor === null) {
            return [
                'kind' => 'system',
                'id' => null,
                'name' => 'Sistem',
                'position' => null,
                'unit' => null,
            ];
        }

        $kind = $actor->isPublicAccount() ? 'public' : 'internal';

        if (! $showsDetails) {
            return [
                'kind' => $kind,
                'id' => null,
                'name' => $kind === 'public'
                    ? 'Pengguna portal publik'
                    : 'Pengguna internal',
                'position' => $kind === 'internal'
                    ? 'Identitas pelaksana dilindungi'
                    : null,
                'unit' => null,
            ];
        }

        $position = $audit->actorPositionAssignment?->position;

        return [
            'kind' => $kind,
            'id' => $actor->getKey(),
            'name' => $this->sanitizer->text($actor->name, 150)
                ?? ($kind === 'public' ? 'Pengguna portal publik' : 'Pengguna internal'),
            'position' => $this->sanitizer->text($position?->name, 150),
            'unit' => $this->sanitizer->text($position?->organizationalUnit?->name, 150),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $target
     * @return array{public_id: string|null, agenda_number: string|null, subject: string, sender: string, source: string|null}|null
     */
    private function target(?array $target): ?array
    {
        if ($target === null) {
            return null;
        }

        $source = $target['source'] ?? null;

        return [
            'public_id' => $this->sanitizer->identifier($target['public_id'] ?? null, 100),
            'agenda_number' => $this->sanitizer->text(
                is_string($target['agenda_number'] ?? null) ? $target['agenda_number'] : null,
                50,
            ),
            'subject' => $this->sanitizer->text(
                is_string($target['subject'] ?? null) ? $target['subject'] : null,
                255,
            ) ?? 'Identitas surat tidak tersedia',
            'sender' => $this->sanitizer->text(
                is_string($target['sender'] ?? null) ? $target['sender'] : null,
                255,
            ) ?? 'Pengirim tidak tersedia',
            'source' => is_string($source) && in_array($source, ['ONLINE', 'MANUAL'], true)
                ? $source
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $document
     * @return array{version_number: int, sha256: string}|null
     */
    private function document(?array $document): ?array
    {
        if ($document === null) {
            return null;
        }

        $version = $document['version_number'] ?? null;
        $sha256 = $this->sanitizer->identifier($document['sha256'] ?? null, 64);

        if (! is_int($version) || $version < 1 || $sha256 === null) {
            return null;
        }

        return [
            'version_number' => $version,
            'sha256' => $sha256,
        ];
    }
}
