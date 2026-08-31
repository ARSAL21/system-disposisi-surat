<?php

use App\Auditing\Contracts\AuditActionContractRegistry;
use App\Auditing\Exceptions\AuditContractViolationException;
use App\Auditing\Guards\AuditSecretScanner;
use App\Enums\AuditAction;

beforeEach(function (): void {
    $this->scanner = new AuditSecretScanner;
    $this->contract = AuditActionContractRegistry::get(AuditAction::SubmissionCreated);
});

it('passes when no sensitive keys, containers, or patterns exist', function (): void {
    $payload = [
        'title' => 'Surat Permohonan',
        'agenda_number' => 'AG-2026-001',
        'details' => [
            'applicant' => 'Budi Utomo',
            'department' => 'Umum',
        ],
    ];

    $this->scanner->scan($this->contract, 'new_values', $payload);
    expect(true)->toBeTrue();
});

it('detects sensitive keys in various naming conventions', function (string $key): void {
    $payload = [
        $key => 'some-secret-value',
    ];

    expect(fn () => $this->scanner->scan($this->contract, 'new_values', $payload))
        ->toThrow(AuditContractViolationException::class, 'Forbidden sensitive key');
})->with([
    'password',
    'user_password',
    'currentPassword',
    'new_password_confirmation',
    'token',
    'authToken',
    'access_token',
    'sessionToken',
    'api_token',
    'secret',
    'client_secret',
    'appSecret',
    'recovery_code',
    'recoveryCodes',
    'recovery_codes',
    'cookie',
    'session_cookie',
    'setCookie',
    'authorization',
    'auth_header',
    'private_key',
    'privateKey',
    'api_key',
    'apiKey',
    'credential',
    'userCredential',
    'passphrase',
    'auth_code',
    'authCode',
    'auth_header',
    'authHeader',
    'auth_key',
    'authKey',
    'session_id',
    'sessionId',
    'jwt',
    'bearer',
]);

it('detects dangerous raw container keys', function (string $containerKey): void {
    $payload = [
        $containerKey => ['some' => 'dump'],
    ];

    expect(fn () => $this->scanner->scan($this->contract, 'metadata', $payload))
        ->toThrow(AuditContractViolationException::class, 'Dangerous container key');
})->with([
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
]);

it('detects sensitive CLI command arguments and authorization headers in values', function (string $commandValue): void {
    $payload = [
        'command' => $commandValue,
    ];

    expect(fn () => $this->scanner->scan($this->contract, 'metadata', $payload))
        ->toThrow(AuditContractViolationException::class, 'Sensitive credential pattern');
})->with([
    'php artisan provision:user --password=SuperSecretPassword123',
    'php artisan app:sync --token=abc12345xyz',
    'php artisan app:sync --secret=supersecret',
    'php artisan app:sync --key=privkey123',
    'php artisan app:sync --api-key=apikeyvalue',
    'curl -H "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9" http://localhost',
    'curl -H "Basic dXNlcm5hbWU6cGFzc3dvcmQ=" http://localhost',
]);

it('detects non-serializable objects and resources in payload', function (): void {
    $payload = [
        'object_data' => new stdClass,
    ];

    expect(fn () => $this->scanner->scan($this->contract, 'new_values', $payload))
        ->toThrow(AuditContractViolationException::class, 'Non-serializable data type');
});
