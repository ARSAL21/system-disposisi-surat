<?php

use App\Actions\RecordAudit;
use App\Enums\AuditAction;
use App\Exceptions\AuditLogMutationDenied;
use App\Models\AuditLog;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function appendOnlyAuditLog(): AuditLog
{
    return app(RecordAudit::class)->execute(
        actor: null,
        action: AuditAction::RoleChanged,
        subjectType: 'role',
        subjectId: 100,
        newValues: ['name' => 'test-role'],
        metadata: ['source' => 'console', 'command' => 'test'],
    );
}

function assertAuditLogWasNotMutated(AuditLog $audit): void
{
    $fresh = $audit->fresh();

    expect($fresh)
        ->not->toBeNull()
        ->action->toBe(AuditAction::RoleChanged->value)
        ->subject_id->toBe(100)
        ->metadata->toBe(['source' => 'console', 'command' => 'test']);
}

test('record audit can append a new audit record', function (): void {
    $audit = appendOnlyAuditLog();

    expect($audit->exists)->toBeTrue()
        ->and(AuditLog::query()->whereKey($audit->getKey())->exists())->toBeTrue();
});

test('instance mutation paths cannot alter an audit record', function (): void {
    $audit = appendOnlyAuditLog();

    expect(fn () => $audit->forceFill(['action' => AuditAction::PermissionChanged->value])->save())
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => $audit->fresh()->updateQuietly(['action' => AuditAction::PermissionChanged->value]))
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => $audit->fresh()->forceFill([
        'action' => AuditAction::PermissionChanged->value,
    ])->saveQuietly())
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => $audit->fresh()->delete())
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => $audit->fresh()->deleteQuietly())
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => $audit->fresh()->forceDelete())
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => AuditLog::destroy($audit->getKey()))
        ->toThrow(AuditLogMutationDenied::class);

    expect(fn () => AuditLog::forceDestroy($audit->getKey()))
        ->toThrow(AuditLogMutationDenied::class);

    assertAuditLogWasNotMutated($audit);
});

test('builder mutation paths cannot alter audit records', function (Closure $mutation): void {
    $audit = appendOnlyAuditLog();

    expect(fn () => $mutation($audit))->toThrow(AuditLogMutationDenied::class);

    assertAuditLogWasNotMutated($audit);
})->with([
    'update' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->update([
        'action' => AuditAction::PermissionChanged->value,
    ]),
    'upsert' => fn (AuditLog $audit) => AuditLog::query()->upsert([
        ['id' => $audit->getKey(), 'action' => AuditAction::PermissionChanged->value],
    ], ['id'], ['action']),
    'update or create' => fn (AuditLog $audit) => AuditLog::query()->updateOrCreate(
        ['request_id' => $audit->request_id],
        ['action' => AuditAction::PermissionChanged->value],
    ),
    'increment or create' => fn (AuditLog $audit) => AuditLog::query()->incrementOrCreate(
        ['request_id' => $audit->request_id],
        'subject_id',
    ),
    'touch' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->touch('created_at'),
    'increment' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->increment('subject_id'),
    'decrement' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->decrement('subject_id'),
    'increment each' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->incrementEach(['subject_id' => 1]),
    'decrement each' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->decrementEach(['subject_id' => 1]),
    'delete' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->delete(),
    'force delete' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->forceDelete(),
    'update or insert' => fn (AuditLog $audit) => AuditLog::query()->updateOrInsert(
        ['request_id' => $audit->request_id],
        ['action' => AuditAction::PermissionChanged->value],
    ),
    'update from' => fn (AuditLog $audit) => AuditLog::query()->whereKey($audit->getKey())->updateFrom([
        'action' => AuditAction::PermissionChanged->value,
    ]),
    'truncate' => fn (AuditLog $audit) => AuditLog::query()->truncate(),
]);
