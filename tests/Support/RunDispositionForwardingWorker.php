<?php

declare(strict_types=1);

use App\Actions\ForwardDisposition;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argc !== 9 || ! in_array($argv[1], ['hold', 'normal'], true)) {
    fwrite(STDERR, "Invalid forwarding concurrency worker arguments.\n");
    exit(2);
}

[$script, $mode, $userId, $parentRecipientId, $targetPositionId, $instructionLabelId, $signalPath, $releasePath] = $argv;

$waitForRelease = static function (string $path): void {
    $deadline = microtime(true) + 20;

    while (! is_file($path)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out while waiting for the forwarding concurrency release signal.');
        }

        usleep(10_000);
    }
};

$transactionStarted = false;

try {
    if ($mode === 'hold') {
        DB::beginTransaction();
        $transactionStarted = true;

        User::query()
            ->whereKey((int) $userId)
            ->lockForUpdate()
            ->firstOrFail();
        $parentRecipient = DispositionRecipient::query()
            ->whereKey((int) $parentRecipientId)
            ->lockForUpdate()
            ->firstOrFail();
        $parentDisposition = Disposition::query()
            ->whereKey($parentRecipient->disposition_id)
            ->lockForUpdate()
            ->firstOrFail();
        IncomingLetter::query()
            ->whereKey($parentDisposition->incoming_letter_id)
            ->lockForUpdate()
            ->firstOrFail();

        file_put_contents($signalPath, 'locked', LOCK_EX);
        $waitForRelease($releasePath);
    } else {
        file_put_contents($signalPath, 'attempting', LOCK_EX);
    }

    app(ForwardDisposition::class)->execute(
        User::query()->findOrFail((int) $userId),
        DispositionRecipient::query()->findOrFail((int) $parentRecipientId),
        [(int) $targetPositionId],
        [(int) $instructionLabelId],
        null,
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

    echo json_encode(['status' => 409], JSON_THROW_ON_ERROR);
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
