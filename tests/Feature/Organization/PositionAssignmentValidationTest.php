<?php

use App\Actions\AssignUserToPosition;
use App\Actions\EndPositionAssignment;
use App\Actions\ReplacePositionHolder;
use App\Enums\AuditAction;
use App\Exceptions\PositionAssignmentConflict;
use App\Exceptions\PositionAssignmentNotAllowed;
use App\Models\AuditLog;
use App\Models\PositionAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

test('assign rejects an occupied position without changing history', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $firstHolder = PositionAssignmentTestData::internalUser();
    $secondHolder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    app(AssignUserToPosition::class)->execute($actor, $firstHolder, $position);

    expect(fn () => app(AssignUserToPosition::class)->execute($actor, $secondHolder, $position))
        ->toThrow(PositionAssignmentConflict::class);

    expect(PositionAssignment::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::PositionAssigned->value)->count())->toBe(1);
});

test('replace requires an occupied position and a different holder', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();

    expect(fn () => app(ReplacePositionHolder::class)->execute($actor, $holder, $position))
        ->toThrow(PositionAssignmentConflict::class);

    app(AssignUserToPosition::class)->execute($actor, $holder, $position);

    expect(fn () => app(ReplacePositionHolder::class)->execute($actor, $holder, $position))
        ->toThrow(PositionAssignmentConflict::class);
});

test('public or inactive users cannot receive position assignments', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $publicUser = User::factory()->create();
    $inactiveInternalUser = PositionAssignmentTestData::internalUser(['is_active' => false]);

    expect(fn () => app(AssignUserToPosition::class)->execute(
        $actor,
        $publicUser,
        PositionAssignmentTestData::position(),
    ))->toThrow(PositionAssignmentNotAllowed::class)
        ->and(fn () => app(AssignUserToPosition::class)->execute(
            $actor,
            $inactiveInternalUser,
            PositionAssignmentTestData::position(),
        ))->toThrow(PositionAssignmentNotAllowed::class);
});

test('inactive positions cannot receive new or replacement assignments', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $holder = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position(['is_active' => false]);

    expect(fn () => app(AssignUserToPosition::class)->execute($actor, $holder, $position))
        ->toThrow(PositionAssignmentNotAllowed::class);
});

test('assignment changes require an eligible internal actor', function (): void {
    $publicActor = User::factory()->create();
    $inactiveActor = PositionAssignmentTestData::internalUser(['is_active' => false]);
    $holder = PositionAssignmentTestData::internalUser();

    expect(fn () => app(AssignUserToPosition::class)->execute(
        $publicActor,
        $holder,
        PositionAssignmentTestData::position(),
    ))->toThrow(PositionAssignmentNotAllowed::class)
        ->and(fn () => app(AssignUserToPosition::class)->execute(
            $inactiveActor,
            $holder,
            PositionAssignmentTestData::position(),
        ))->toThrow(PositionAssignmentNotAllowed::class);
});

test('an assignment cannot end at the same server time it started', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-08-27 08:00:00.100000'));

    $actor = PositionAssignmentTestData::internalUser();
    $assignment = app(AssignUserToPosition::class)->execute(
        $actor,
        PositionAssignmentTestData::internalUser(),
        PositionAssignmentTestData::position(),
    );

    expect(fn () => app(EndPositionAssignment::class)->execute($actor, $assignment))
        ->toThrow(PositionAssignmentConflict::class);

    expect($assignment->refresh()->isActive())->toBeTrue();
});

test('an ended assignment cannot be ended again', function (): void {
    $actor = PositionAssignmentTestData::internalUser();
    $assignment = PositionAssignmentTestData::assignment(
        PositionAssignmentTestData::internalUser(),
        PositionAssignmentTestData::position(),
        $actor,
        now()->subHours(2),
        now()->subHour(),
    );

    expect(fn () => app(EndPositionAssignment::class)->execute($actor, $assignment))
        ->toThrow(PositionAssignmentConflict::class);
});
