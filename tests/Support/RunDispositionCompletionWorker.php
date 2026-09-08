<?php

declare(strict_types=1);

use App\Actions\CompleteDispositionBranch;
use App\Exceptions\DispositionStateConflict;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argc !== 7 || ! in_array($argv[1], ['hold', 'normal'], true)) {
    fwrite(STDERR, "Invalid concurrency worker arguments.\n");
    exit(2);
}

[$script, $mode, $userId, $branchId, $letterId, $signalPath, $releasePath] = $argv;

$waitForSignal = static function (string $path): void {
    $deadline = microtime(true) + 20;

    while (! is_file($path)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out while waiting for the concurrency release signal.');
        }

        usleep(10_000);
    }
};

$transactionStarted = false;

try {
    if ($mode === 'hold') {
        DB::beginTransaction();
        $transactionStarted = true;

        IncomingLetter::query()
            ->whereKey((int) $letterId)
            ->lockForUpdate()
            ->firstOrFail();

        file_put_contents($signalPath, 'locked', LOCK_EX);
        $waitForSignal($releasePath);
    } else {
        file_put_contents($signalPath, 'attempting', LOCK_EX);
    }

    $actor = User::query()->findOrFail((int) $userId);
    $branch = DispositionRecipient::query()->findOrFail((int) $branchId);

    app(CompleteDispositionBranch::class)->execute(
        $actor,
        $branch,
        'Penyelesaian cabang melalui smoke test konkurensi MySQL.',
    );

    if ($transactionStarted) {
        DB::commit();
        $transactionStarted = false;
    }

    echo json_encode(['status' => 200], JSON_THROW_ON_ERROR);
} catch (DispositionStateConflict $exception) {
    if ($transactionStarted && DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    echo json_encode([
        'status' => $exception->render()->getStatusCode(),
        'exception' => $exception::class,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    if ($transactionStarted && DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    echo json_encode([
        'status' => 500,
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
