<?php

namespace App\Auditing;

use Illuminate\Support\Str;

final class PrivilegeAuditPayloadSanitizer
{
    private const array SAFE_CHANGE_FIELDS = [
        'name',
        'email',
        'account_type',
        'is_active',
        'email_verified_at',
        'guard_name',
        'roles',
        'permissions',
    ];

    /** @var list<string> */
    private const array SAFE_CONSOLE_COMMANDS = [
        'internal:user',
        'authorization:sync',
        'authorization:super-admin',
    ];

    /**
     * @param  array<string, mixed>|null  $changes
     * @return array<string, string|int|bool|list<string>|null>|null
     */
    public function changes(?array $changes): ?array
    {
        if ($changes === null) {
            return null;
        }

        $safe = [];

        foreach (self::SAFE_CHANGE_FIELDS as $field) {
            if (! array_key_exists($field, $changes)) {
                continue;
            }

            $value = $this->value($changes[$field]);

            if ($value !== null || $changes[$field] === null) {
                $safe[$field] = $value;
            }
        }

        return $safe === [] ? null : $safe;
    }

    public function command(mixed $command): ?string
    {
        return is_string($command) && in_array($command, self::SAFE_CONSOLE_COMMANDS, true)
            ? $command
            : null;
    }

    public function ipAddress(?string $ipAddress): ?string
    {
        return $ipAddress !== null && filter_var($ipAddress, FILTER_VALIDATE_IP) !== false
            ? $ipAddress
            : null;
    }

    public function userAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $userAgent);

        return $normalized === null ? null : $this->text($normalized, 500);
    }

    public function identifier(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || preg_match('/\A[a-zA-Z0-9._:-]+\z/', $value) !== 1) {
            return null;
        }

        return Str::limit($value, $limit, '');
    }

    public function text(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === null ? null : Str::limit($normalized, $limit, '');
    }

    /** @return string|int|bool|list<string>|null */
    private function value(mixed $value): string|int|bool|array|null
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            return $this->text($value, 500);
        }

        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        return array_map(
            fn (mixed $entry): string => $this->text(is_scalar($entry) ? (string) $entry : '[nilai tidak ditampilkan]', 150) ?? '',
            array_slice($value, 0, 100),
        );
    }
}
