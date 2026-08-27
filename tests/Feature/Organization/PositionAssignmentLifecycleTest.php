<?php

use App\Actions\AssignUserToPosition;
use App\Actions\EndPositionAssignment;
use App\Actions\RecordAudit;
use App\Actions\ReplacePositionHolder;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PositionAssignment;
use App\Services\PositionAssignmentEligibility;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

test('an internal user can be assigned to a vacant position', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-08-27 08:00:00.100000'));

    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();

    $assignment = app(AssignUserToPosition::class)->execute($actor, $holder, $position);

    expect($assignment->user_id)->toBe($holder->getKey())
        ->and($assignment->position_id)->toBe($position->getKey())
        ->and($assignment->assigned_by_user_id)->toBe($actor->getKey())
        ->and($assignment->isActive())->toBeTrue()
        ->and($assignment->started_at->toISOString())->toBe(now()->toISOString());

    $audit = AuditLog::query()
        ->where('action', AuditAction::PositionAssigned->value)
        ->sole();

    expect($audit->actor_user_id)->toBe($actor->getKey())
        ->and($audit->subject_type)->toBe('position')
        ->and($audit->subject_id)->toBe($position->getKey())
        ->and($audit->new_values['assignment_id'])->toBe($assignment->getKey());
});

test('replacing a holder is atomic and preserves the old assignment', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-08-27 08:00:00.100000'));

    $actor = PositionAssignmentTestData::internalUser();
    $oldHolder = PositionAssignmentTestData::internalUser();
    $newHolder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    $oldAssignment = app(AssignUserToPosition::class)->execute($actor, $oldHolder, $position);

    $this->travelTo(CarbonImmutable::parse('2026-08-27 09:00:00.200000'));
    $newAssignment = app(ReplacePositionHolder::class)->execute($actor, $newHolder, $position);
    $oldAssignment->refresh();

    expect($oldAssignment->ended_at?->toISOString())
        ->toBe($newAssignment->started_at->toISOString())
        ->and($oldAssignment->isActive())->toBeFalse()
        ->and($newAssignment->isActive())->toBeTrue()
        ->and(PositionAssignment::query()->where('position_id', $position->getKey())->count())->toBe(2)
        ->and(PositionAssignment::query()->active()->where('position_id', $position->getKey())->count())->toBe(1);

    $audit = AuditLog::query()
        ->where('action', AuditAction::PositionHolderReplaced->value)
        ->sole();

    expect($audit->old_values['assignment_id'])->toBe($oldAssignment->getKey())
        ->and($audit->new_values['assignment_id'])->toBe($newAssignment->getKey());
});

test('an active assignment can be ended without creating a replacement', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-08-27 08:00:00.100000'));

    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    $assignment = app(AssignUserToPosition::class)->execute($actor, $holder, $position);

    $this->travelTo(CarbonImmutable::parse('2026-08-27 10:00:00.200000'));
    $endedAssignment = app(EndPositionAssignment::class)->execute($actor, $assignment);

    expect($endedAssignment->isActive())->toBeFalse()
        ->and($position->activeAssignment()->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('action', AuditAction::PositionAssignmentEnded->value)
            ->count())->toBe(1);
});

test('one user may actively hold more than one position', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();

    app(AssignUserToPosition::class)->execute(
        $actor,
        $holder,
        PositionAssignmentTestData::position(),
    );
    app(AssignUserToPosition::class)->execute(
        $actor,
        $holder,
        PositionAssignmentTestData::position(),
    );

    expect($holder->activePositionAssignments()->count())->toBe(2);
});

test('assignment and audit roll back together when audit recording fails', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit failed.'));

    $action = new AssignUserToPosition(
        app(PositionAssignmentEligibility::class),
        $recordAudit,
    );

    expect(fn () => $action->execute($actor, $holder, $position))
        ->toThrow(RuntimeException::class, 'Audit failed.');

    expect(PositionAssignment::query()->count())->toBe(0)
        ->and(AuditLog::query()->count())->toBe(0);
});
