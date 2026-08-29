<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\PositionLevel;
use App\Organization\OrganizationCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SynchronizePositionLevelCatalog
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @return array{created: list<string>, changed: list<string>, unknown: list<string>}
     */
    public function execute(): array
    {
        $operationId = Str::uuid()->toString();

        return DB::transaction(function () use ($operationId): array {
            PositionLevel::query()->lockForUpdate()->get();
            $created = [];
            $changed = [];
            $before = [];
            $after = [];

            foreach (OrganizationCatalog::positionLevelDefinitions() as $definition) {
                $level = PositionLevel::query()->where('code', $definition['code'])->first();

                if ($level === null) {
                    $level = new PositionLevel;
                    $level->code = $definition['code'];
                }

                $original = $level->exists ? $this->snapshot($level) : null;
                $level->name = $definition['name'];
                $level->hierarchy_order = $definition['hierarchy_order'];
                $level->is_active = $definition['is_active'];

                if (! $level->exists) {
                    $level->save();
                    $created[] = $level->code;
                    $before[$level->code] = null;
                    $after[$level->code] = $this->snapshot($level);

                    continue;
                }

                if (! $level->isDirty()) {
                    continue;
                }

                $level->save();
                $changed[] = $level->code;
                $before[$level->code] = $original;
                $after[$level->code] = $this->snapshot($level);
            }

            if ($created !== [] || $changed !== []) {
                $this->recordAudit->execute(
                    actor: null,
                    action: AuditAction::PositionLevelCatalogSynchronized,
                    subjectType: 'position_level_catalog',
                    subjectId: null,
                    oldValues: $before,
                    newValues: $after,
                    metadata: [
                        'source' => 'console',
                        'command' => 'organization:sync-levels',
                    ],
                    requestId: $operationId,
                );
            }

            return [
                'created' => $created,
                'changed' => $changed,
                'unknown' => array_values(PositionLevel::query()
                    ->whereNotIn('code', OrganizationCatalog::positionLevelCodes())
                    ->orderBy('code')
                    ->pluck('code')
                    ->map(static fn (mixed $code): string => (string) $code)
                    ->values()
                    ->all()),
            ];
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(PositionLevel $level): array
    {
        return [
            'code' => $level->code,
            'name' => $level->name,
            'hierarchy_order' => $level->hierarchy_order,
            'is_active' => $level->is_active,
        ];
    }
}
