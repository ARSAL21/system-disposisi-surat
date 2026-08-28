<?php

namespace App\Auditing;

use App\Models\AuditLog;

final class PrivilegeAuditPresenter
{
    public function __construct(private readonly PrivilegeAuditPayloadSanitizer $sanitizer) {}

    /**
     * @param  array{label: string, secondary: string|null}|null  $currentTarget
     * @return array<string, mixed>
     */
    public function present(AuditLog $audit, ?array $currentTarget): array
    {
        $source = $this->source($audit);

        return [
            'id' => $audit->getKey(),
            'action' => $audit->action,
            'change' => $this->sanitizer->identifier($audit->metadata['change'] ?? null, 100),
            'actor' => $this->actor($audit),
            'target' => $this->target($audit, $currentTarget),
            'before' => $this->sanitizer->changes($audit->old_values),
            'after' => $this->sanitizer->changes($audit->new_values),
            'source' => $source,
            'command' => $source === 'console'
                ? $this->sanitizer->command($audit->metadata['command'] ?? null)
                : null,
            'request_id' => $this->sanitizer->identifier($audit->request_id, 64),
            'ip_address' => $this->sanitizer->ipAddress($audit->ip_address),
            'user_agent' => $this->sanitizer->userAgent($audit->user_agent),
            'created_at' => $audit->created_at->toISOString(),
        ];
    }

    /** @return array{kind: string, id: int|null, name: string, email: string|null} */
    private function actor(AuditLog $audit): array
    {
        if ($audit->actor === null) {
            return [
                'kind' => 'system',
                'id' => null,
                'name' => 'Sistem',
                'email' => null,
            ];
        }

        return [
            'kind' => 'user',
            'id' => $audit->actor->getKey(),
            'name' => $this->sanitizer->text($audit->actor->name, 150) ?? 'User internal',
            'email' => $this->sanitizer->text($audit->actor->email, 255),
        ];
    }

    /**
     * @param  array{label: string, secondary: string|null}|null  $currentTarget
     * @return array{type: string, id: int|null, label: string, secondary: string|null, exists: bool}
     */
    private function target(AuditLog $audit, ?array $currentTarget): array
    {
        if ($currentTarget !== null) {
            return [
                'type' => $audit->subject_type,
                'id' => $audit->subject_id,
                'label' => $this->sanitizer->text($currentTarget['label'], 255) ?? 'Target audit',
                'secondary' => $this->sanitizer->text($currentTarget['secondary'], 255),
                'exists' => true,
            ];
        }

        $label = $this->snapshotValue($audit, 'name')
            ?? $this->snapshotValue($audit, 'email')
            ?? sprintf('%s #%s', ucfirst($audit->subject_type), $audit->subject_id ?? 'tanpa-id');
        $secondary = $this->snapshotValue($audit, 'email');

        if ($secondary === $label) {
            $secondary = null;
        }

        return [
            'type' => $audit->subject_type,
            'id' => $audit->subject_id,
            'label' => $this->sanitizer->text($label, 255) ?? 'Target audit',
            'secondary' => $secondary === null
                ? 'Snapshot audit'
                : $this->sanitizer->text($secondary, 255),
            'exists' => false,
        ];
    }

    private function snapshotValue(AuditLog $audit, string $key): ?string
    {
        foreach ([$audit->new_values, $audit->old_values] as $values) {
            $value = $values[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function source(AuditLog $audit): string
    {
        $source = $audit->metadata['source'] ?? null;

        if (is_string($source) && in_array($source, PrivilegeAuditCatalog::sources(), true)) {
            return $source;
        }

        return $audit->actor_user_id === null ? 'console' : 'web';
    }
}
