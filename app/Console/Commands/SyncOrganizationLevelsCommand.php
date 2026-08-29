<?php

namespace App\Console\Commands;

use App\Actions\SynchronizePositionLevelCatalog;
use Illuminate\Console\Command;
use Throwable;

class SyncOrganizationLevelsCommand extends Command
{
    protected $signature = 'organization:sync-levels';

    protected $description = 'Synchronize the protected workflow Position Level catalog';

    public function handle(SynchronizePositionLevelCatalog $synchronize): int
    {
        try {
            $result = $synchronize->execute();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Katalog level jabatan tidak dapat disinkronkan. Periksa log aplikasi.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Katalog level jabatan tersinkronisasi: %d dibuat, %d diperbarui.',
            count($result['created']),
            count($result['changed']),
        ));

        if ($result['unknown'] !== []) {
            $this->components->warn('Level di luar katalog dipertahankan sebagai drift: '.implode(', ', $result['unknown']));
        }

        return self::SUCCESS;
    }
}
