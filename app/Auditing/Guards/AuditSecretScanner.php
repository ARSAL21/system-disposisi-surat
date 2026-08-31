<?php

namespace App\Auditing\Guards;

use App\Auditing\Contracts\AuditContract;
use App\Auditing\Exceptions\AuditContractViolationException;

class AuditSecretScanner
{
    private const FORBIDDEN_STEMS = [
        'password',
        'token',
        'secret',
        'recoverycode',
        'cookie',
        'authorization',
        'authheader',
        'authtoken',
        'authkey',
        'authsecret',
        'authcode',
        'authenticator',
        'privatekey',
        'apikey',
        'clientkey',
        'secretkey',
        'bearer',
        'passphrase',
        'sessionid',
        'jwt',
        'credential',
    ];

    private const BLOCKED_CONTAINER_KEYS = [
        'headers',
        'request',
        'requests',
        'payload',
        'arguments',
        'cookies',
        'session',
        'sessions',
        'env',
        'environment',
        'server',
        'credentials',
    ];

    private const SENSITIVE_VALUE_PATTERNS = [
        '/(--?(?:password|token|secret|key|api-?key|private-?key|auth|credential)[=\s][^\s]+)/i',
        '/(bearer\s+[A-Za-z0-9_\-.~+\/=]+)/i',
        '/(basic\s+[A-Za-z0-9+\/=]{10,})/i',
    ];

    /**
     * @param  array<array-key, mixed>|null  $payload
     */
    public function scan(
        AuditContract $contract,
        string $payloadName,
        ?array $payload,
        string $path = '',
    ): void {
        if ($payload === null) {
            return;
        }

        foreach ($payload as $key => $value) {
            $currentPath = $path === '' ? (string) $key : $path.'.'.$key;

            if (is_string($key)) {
                $this->validateKey($contract, $payloadName, $key, $currentPath);
            }

            if (is_array($value)) {
                $this->scan($contract, $payloadName, $value, $currentPath);
            } elseif (is_string($value)) {
                $this->validateStringValue($contract, $payloadName, (string) $key, $value, $currentPath);
            } elseif (is_object($value) || is_resource($value)) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    sprintf(
                        'Non-serializable data type [%s] detected in %s at [%s].',
                        get_debug_type($value),
                        $payloadName,
                        $currentPath,
                    ),
                );
            }
        }
    }

    private function validateKey(
        AuditContract $contract,
        string $payloadName,
        string $key,
        string $currentPath,
    ): void {
        $normalizedKey = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $key));

        if (in_array($normalizedKey, self::BLOCKED_CONTAINER_KEYS, true)) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                sprintf(
                    'Dangerous container key [%s] is not permitted in %s at [%s].',
                    $key,
                    $payloadName,
                    $currentPath,
                ),
            );
        }

        foreach (self::FORBIDDEN_STEMS as $stem) {
            if (str_contains($normalizedKey, $stem)) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    sprintf(
                        'Forbidden sensitive key [%s] detected in %s at [%s].',
                        $key,
                        $payloadName,
                        $currentPath,
                    ),
                );
            }
        }
    }

    private function validateStringValue(
        AuditContract $contract,
        string $payloadName,
        string $key,
        string $value,
        string $currentPath,
    ): void {
        foreach (self::SENSITIVE_VALUE_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    sprintf(
                        'Sensitive credential pattern detected in %s value at [%s].',
                        $payloadName,
                        $currentPath,
                    ),
                );
            }
        }
    }
}
