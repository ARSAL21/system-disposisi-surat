<?php

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

test('position assignment schema and indexes are available', function (): void {
    expect(Schema::hasColumns('position_assignments', [
        'id', 'user_id', 'position_id', 'started_at', 'ended_at',
        'assigned_by_user_id', 'created_at', 'updated_at',
    ]))->toBeTrue();

    $indexes = collect(Schema::getIndexes('position_assignments'))
        ->map(fn (array $index): array => $index['columns'])
        ->values();

    expect($indexes->contains(['position_id', 'ended_at']))->toBeTrue()
        ->and($indexes->contains(['user_id', 'ended_at']))->toBeTrue()
        ->and($indexes->contains(['assigned_by_user_id']))->toBeTrue();
});

test('position assignment exposes historical relationships', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    $assignment = PositionAssignmentTestData::assignment($holder, $position, $actor);

    $auditLog = new AuditLog;
    $auditLog->actor_user_id = $actor->getKey();
    $auditLog->actor_position_assignment_id = $assignment->getKey();
    $auditLog->action = 'TEST_ACTION';
    $auditLog->subject_type = 'position';
    $auditLog->subject_id = $position->getKey();
    $auditLog->save();

    expect($assignment->user->is($holder))->toBeTrue()
        ->and($assignment->position->is($position))->toBeTrue()
        ->and($assignment->assignedBy?->is($actor))->toBeTrue()
        ->and($holder->activePositionAssignments->first()?->is($assignment))->toBeTrue()
        ->and($position->activeAssignment?->is($assignment))->toBeTrue()
        ->and($auditLog->actorPositionAssignment?->is($assignment))->toBeTrue();
});

test('historical identity fields and deletion are blocked', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $replacement = PositionAssignmentTestData::internalUser();
    $assignment = PositionAssignmentTestData::assignment(
        $holder,
        PositionAssignmentTestData::position(),
        $actor,
    );

    $assignment->user_id = $replacement->getKey();

    expect(fn () => $assignment->save())->toThrow(LogicException::class)
        ->and(fn () => $assignment->refresh()->delete())->toThrow(LogicException::class);
});

test('an assignment can only be ended once with a valid period', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $startedAt = CarbonImmutable::parse('2026-08-27 08:00:00.000001');
    $assignment = PositionAssignmentTestData::assignment(
        $holder,
        PositionAssignmentTestData::position(),
        $actor,
        $startedAt,
    );

    $assignment->ended_at = $startedAt->addHour();
    $assignment->save();
    $assignment->ended_at = $startedAt->addHours(2);

    expect(fn () => $assignment->save())->toThrow(LogicException::class);
});

test('foreign keys preserve assignment history', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    PositionAssignmentTestData::assignment($holder, $position, $actor);

    expect(fn () => $holder->delete())->toThrow(QueryException::class)
        ->and(fn () => $position->delete())->toThrow(QueryException::class);
});
