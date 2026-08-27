<?php

use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionLevel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function createM2OrganizationalUnit(
    ?string $code = null,
    ?OrganizationalUnit $parent = null,
): OrganizationalUnit {
    static $sequence = 0;

    $unit = new OrganizationalUnit;
    $unit->parent_id = $parent?->getKey();
    $unit->code = $code;
    $unit->name = 'Organizational Unit '.++$sequence;
    $unit->save();

    return $unit->refresh();
}

function createM2PositionLevel(string $code, int $hierarchyOrder = 10): PositionLevel
{
    $level = new PositionLevel;
    $level->code = $code;
    $level->name = 'Position Level '.$code;
    $level->hierarchy_order = $hierarchyOrder;
    $level->save();

    return $level->refresh();
}

function createM2Position(
    PositionLevel $level,
    string $code,
    ?OrganizationalUnit $unit = null,
): Position {
    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->organizational_unit_id = $unit?->getKey();
    $position->code = $code;
    $position->name = 'Position '.$code;
    $position->save();

    return $position->refresh();
}

test('position foundation tables and indexes are available', function (): void {
    expect(Schema::hasColumns('organizational_units', [
        'id', 'parent_id', 'code', 'name', 'is_active', 'created_at', 'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('position_levels', [
            'id', 'code', 'name', 'hierarchy_order', 'is_active', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('positions', [
            'id', 'position_level_id', 'organizational_unit_id', 'code', 'name',
            'is_active', 'created_at', 'updated_at',
        ]))->toBeTrue();

    $organizationalUnitIndexes = collect(Schema::getIndexes('organizational_units'))->pluck('name');
    $positionLevelIndexes = collect(Schema::getIndexes('position_levels'))->pluck('name');
    $positionIndexes = collect(Schema::getIndexes('positions'))->pluck('name');

    expect($organizationalUnitIndexes)->toContain('organizational_units_is_active_index')
        ->and($positionLevelIndexes)->toContain('position_levels_is_active_index')
        ->and($positionIndexes)->toContain('positions_is_active_index');
});

test('organization and position relationships preserve their distinct concepts', function (): void {
    $parent = createM2OrganizationalUnit('SECRETARIAT');
    $unit = createM2OrganizationalUnit('GENERAL-AFFAIRS', $parent);
    $level = createM2PositionLevel('GENERAL_AFFAIRS', 10);
    $position = createM2Position($level, 'HEAD-GENERAL-AFFAIRS', $unit);

    expect($unit->parent->is($parent))->toBeTrue()
        ->and($parent->children->first()?->is($unit))->toBeTrue()
        ->and($position->positionLevel->is($level))->toBeTrue()
        ->and($position->organizationalUnit?->is($unit))->toBeTrue()
        ->and($level->positions->first()?->is($position))->toBeTrue()
        ->and($unit->positions->first()?->is($position))->toBeTrue();
});

test('organizational unit is optional for a position', function (): void {
    $level = createM2PositionLevel('EXECUTIVE_ENTRY');
    $position = createM2Position($level, 'MAYOR');

    expect($position->organizational_unit_id)->toBeNull()
        ->and($position->organizationalUnit)->toBeNull();
});

test('master data is active by default and uses native casts', function (): void {
    $unit = createM2OrganizationalUnit();
    $level = createM2PositionLevel('ASSISTANT', 30);
    $position = createM2Position($level, 'ASSISTANT-I', $unit);

    expect($unit->is_active)->toBeTrue()
        ->and($level->is_active)->toBeTrue()
        ->and($level->hierarchy_order)->toBeInt()->toBe(30)
        ->and($position->is_active)->toBeTrue();
});

test('nullable organizational unit codes do not have to be unique', function (): void {
    createM2OrganizationalUnit();
    createM2OrganizationalUnit();

    expect(OrganizationalUnit::query()->whereNull('code')->count())->toBe(2);
});

test('organizational unit codes are unique when present', function (): void {
    createM2OrganizationalUnit('LEGAL');

    expect(fn () => createM2OrganizationalUnit('LEGAL'))
        ->toThrow(QueryException::class);
});

test('position level codes are unique', function (): void {
    createM2PositionLevel('SECTION_HEAD');

    expect(fn () => createM2PositionLevel('SECTION_HEAD'))
        ->toThrow(QueryException::class);
});

test('position codes are unique', function (): void {
    $level = createM2PositionLevel('SECTION_HEAD');
    createM2Position($level, 'HEAD-LEGAL');

    expect(fn () => createM2Position($level, 'HEAD-LEGAL'))
        ->toThrow(QueryException::class);
});

test('organizational parents cannot be deleted while children reference them', function (): void {
    $parent = createM2OrganizationalUnit('PARENT');
    createM2OrganizationalUnit('CHILD', $parent);

    expect(fn () => $parent->delete())->toThrow(QueryException::class);
});

test('position references prevent deleting their level or organizational unit', function (): void {
    $unit = createM2OrganizationalUnit('GENERAL-AFFAIRS');
    $level = createM2PositionLevel('GENERAL_AFFAIRS');
    createM2Position($level, 'HEAD-GENERAL-AFFAIRS', $unit);

    expect(fn () => $level->delete())->toThrow(QueryException::class)
        ->and(fn () => $unit->delete())->toThrow(QueryException::class);
});
