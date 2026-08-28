<?php

namespace App\Console\Commands;

use App\Actions\SynchronizeAuthorizationCatalog;
use Illuminate\Console\Command;
use Throwable;

class SyncAuthorizationCommand extends Command
{
    protected $signature = 'authorization:sync';

    protected $description = 'Synchronize the official role and permission catalog';

    public function handle(SynchronizeAuthorizationCatalog $synchronizeAuthorizationCatalog): int
    {
        try {
            $result = $synchronizeAuthorizationCatalog->execute();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('The authorization catalog could not be synchronized. Check the application log.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Authorization catalog synchronized: %d permissions created, %d roles created, %d role mappings changed.',
            count($result['created_permissions']),
            count($result['created_roles']),
            count($result['changed_roles']),
        ));

        $this->warnAboutDrift('permissions', $result['unknown_permissions']);

        return self::SUCCESS;
    }

    /** @param list<string> $names */
    private function warnAboutDrift(string $resource, array $names): void
    {
        if ($names === []) {
            return;
        }

        $this->components->warn(sprintf(
            'Unknown %s were preserved: %s',
            $resource,
            implode(', ', $names),
        ));
    }
}
