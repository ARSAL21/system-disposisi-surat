<?php

use App\Auditing\Exceptions\AuditContractViolationException;
use App\Auditing\Guards\AuditLogGuard;
use App\Enums\AuditAction;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->guard = new AuditLogGuard;
});

function createPersistedPositionAssignment(User $user, bool $active = true, ?CarbonImmutable $startedAt = null): PositionAssignment
{
    $level = new PositionLevel;
    $level->code = 'LVL_TEST_'.uniqid();
    $level->name = 'Test Level';
    $level->hierarchy_order = 10;
    $level->is_active = true;
    $level->save();

    $position = new Position;
    $position->position_level_id = $level->id;
    $position->code = 'POS_TEST_'.uniqid();
    $position->name = 'Test Position';
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->id;
    $assignment->position_id = $position->id;
    $assignment->started_at = $startedAt ?? CarbonImmutable::now()->subHour();
    $assignment->ended_at = $active ? null : CarbonImmutable::now()->subMinute();
    $assignment->save();

    return $assignment;
}

it('passes validation for valid audit input with active persisted position assignment', function (): void {
    $actor = User::factory()->create();
    $assignment = createPersistedPositionAssignment($actor, active: true);

    $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 10,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        metadata: ['channel' => 'web'],
        requestId: 'req-12345:sub_1.0',
        actorPositionAssignment: $assignment,
    );

    expect(true)->toBeTrue();
});

it('rejects invalid subject type for the given action', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'invalid_subject_type',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
    ))->toThrow(AuditContractViolationException::class, 'Subject type [invalid_subject_type] is not allowed.');
});

it('rejects missing subject ID when required by contract', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionCreated,
        subjectType: 'letter_submission',
        subjectId: null,
        newValues: ['status' => 'DRAFT'],
    ))->toThrow(AuditContractViolationException::class, 'Subject ID is required for this action.');
});

it('rejects non-null subject ID when contract specifies it must be null', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::PositionLevelCatalogSynchronized,
        subjectType: 'position_level_catalog',
        subjectId: 999,
        newValues: ['synchronized' => true],
    ))->toThrow(AuditContractViolationException::class, 'Subject ID must be null for this action.');
});

it('rejects create mutation with old_values', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionCreated,
        subjectType: 'letter_submission',
        subjectId: 1,
        oldValues: ['status' => 'SOMETHING'],
        newValues: ['status' => 'DRAFT'],
    ))->toThrow(AuditContractViolationException::class, 'Create mutation must not have old_values.');
});

it('rejects create mutation without new_values', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionCreated,
        subjectType: 'letter_submission',
        subjectId: 1,
        oldValues: null,
        newValues: null,
    ))->toThrow(AuditContractViolationException::class, 'Create mutation requires non-empty new_values.');
});

it('rejects update mutation without old_values', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionUpdated,
        subjectType: 'letter_submission',
        subjectId: 1,
        oldValues: null,
        newValues: ['subject' => 'New'],
    ))->toThrow(AuditContractViolationException::class, 'Update mutation requires non-empty old_values.');
});

it('rejects delete mutation with new_values', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionDraftDeleted,
        subjectType: 'letter_submission',
        subjectId: 1,
        oldValues: ['status' => 'DRAFT'],
        newValues: ['status' => 'DELETED'],
    ))->toThrow(AuditContractViolationException::class, 'Delete mutation must not have new_values.');
});

it('rejects missing position assignment when required', function (): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        actorPositionAssignment: null,
    ))->toThrow(AuditContractViolationException::class, 'Active Position Assignment is required for this action.');
});

it('rejects unpersisted position assignment', function (): void {
    $actor = User::factory()->create();
    $unpersisted = new PositionAssignment;
    $unpersisted->user_id = $actor->id;

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        actorPositionAssignment: $unpersisted,
    ))->toThrow(AuditContractViolationException::class, 'The provided Position Assignment must be a persisted database record.');
});

it('rejects position assignment belonging to another user', function (): void {
    $actor = User::factory()->create();
    $otherUser = User::factory()->create();
    $assignment = createPersistedPositionAssignment($otherUser, active: true);

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        actorPositionAssignment: $assignment,
    ))->toThrow(AuditContractViolationException::class, 'The provided Position Assignment is not an active, started database record belonging to the acting user.');
});

it('rejects inactive ended position assignment', function (): void {
    $actor = User::factory()->create();
    $assignment = createPersistedPositionAssignment($actor, active: false);

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        actorPositionAssignment: $assignment,
    ))->toThrow(AuditContractViolationException::class, 'The provided Position Assignment is not an active, started database record belonging to the acting user.');
});

it('rejects position assignment with future started_at', function (): void {
    $actor = User::factory()->create();
    $assignment = createPersistedPositionAssignment($actor, active: true, startedAt: CarbonImmutable::now()->addDay());

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::LetterRegistered,
        subjectType: 'incoming_letter',
        subjectId: 1,
        oldValues: ['status' => 'READY_FOR_APPROVAL'],
        newValues: ['status' => 'REGISTERED'],
        actorPositionAssignment: $assignment,
    ))->toThrow(AuditContractViolationException::class, 'The provided Position Assignment is not an active, started database record belonging to the acting user.');
});

it('rejects position assignment when forbidden for public submission events', function (): void {
    $actor = User::factory()->create();
    $assignment = createPersistedPositionAssignment($actor, active: true);

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionCreated,
        subjectType: 'letter_submission',
        subjectId: 1,
        newValues: ['status' => 'DRAFT'],
        actorPositionAssignment: $assignment,
    ))->toThrow(AuditContractViolationException::class, 'Position Assignment is forbidden for this action.');
});

it('rejects invalid request_id with control characters, spaces, or invalid format', function (string $invalidRequestId): void {
    $actor = User::factory()->create();

    expect(fn () => $this->guard->validate(
        actor: $actor,
        action: AuditAction::SubmissionCreated,
        subjectType: 'letter_submission',
        subjectId: 1,
        newValues: ['status' => 'DRAFT'],
        requestId: $invalidRequestId,
    ))->toThrow(AuditContractViolationException::class, 'Request ID');
})->with([
    '',
    'req id with spaces',
    "req\nnewline",
    "req\r\ninjection",
    'req<script>',
    'req;DROP TABLE users;',
    'req"quotes"',
    str_repeat('a', 65),
]);

it('rejects console audit metadata when command contains arguments or invalid characters', function (string $invalidCommand): void {
    expect(fn () => $this->guard->validate(
        actor: null,
        action: AuditAction::PositionLevelCatalogSynchronized,
        subjectType: 'position_level_catalog',
        subjectId: null,
        oldValues: ['levels' => []],
        newValues: ['levels' => ['LVL_1']],
        metadata: [
            'source' => 'console',
            'command' => $invalidCommand,
        ],
    ))->toThrow(AuditContractViolationException::class, 'Console command name must be a valid command identifier');
})->with([
    'organization:sync-levels --force',
    'app:seed --class=UserSeeder',
    'command with spaces',
    'command\nnewline',
    'command;rm -rf /',
]);
