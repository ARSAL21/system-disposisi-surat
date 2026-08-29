<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PositionLevel;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization level command exact syncs the protected workflow catalog', function (): void {
    $this->artisan('organization:sync-levels')->assertSuccessful();

    expect(PositionLevel::query()->orderBy('hierarchy_order')->pluck('code')->all())
        ->toBe(OrganizationCatalog::positionLevelCodes())
        ->and(AuditLog::query()
            ->where('action', AuditAction::PositionLevelCatalogSynchronized->value)
            ->count())->toBe(1);

    $this->artisan('organization:sync-levels')->assertSuccessful();

    expect(AuditLog::query()
        ->where('action', AuditAction::PositionLevelCatalogSynchronized->value)
        ->count())->toBe(1);
});

test('organization level command repairs protected drift and preserves unknown levels', function (): void {
    $this->artisan('organization:sync-levels')->assertSuccessful();

    PositionLevel::query()->where('code', 'GENERAL_AFFAIRS')->update([
        'name' => 'Changed outside catalog',
        'hierarchy_order' => 999,
        'is_active' => false,
    ]);

    $unknown = new PositionLevel;
    $unknown->code = 'LEGACY_LEVEL';
    $unknown->name = 'Legacy';
    $unknown->hierarchy_order = 900;
    $unknown->is_active = true;
    $unknown->save();

    $this->artisan('organization:sync-levels')
        ->expectsOutputToContain('LEGACY_LEVEL')
        ->assertSuccessful();

    $generalAffairs = PositionLevel::query()->where('code', 'GENERAL_AFFAIRS')->firstOrFail();

    expect($generalAffairs->name)->toBe('Bagian Umum / Tata Usaha')
        ->and($generalAffairs->hierarchy_order)->toBe(10)
        ->and($generalAffairs->is_active)->toBeTrue()
        ->and($unknown->fresh())->not->toBeNull()
        ->and(AuditLog::query()
            ->where('action', AuditAction::PositionLevelCatalogSynchronized->value)
            ->count())->toBe(2);
});
