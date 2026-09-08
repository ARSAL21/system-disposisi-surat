<?php

declare(strict_types=1);

use App\Actions\AssignOutgoingLetterNumber;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argc !== 8) {
    fwrite(STDERR, "Invalid outgoing-number worker arguments.\n");
    exit(2);
}

[$script, $userId, $outgoingLetterId, $number, $letterDate, $readyPath, $releasePath] = $argv;

$waitForRelease = static function (string $path): void {
    $deadline = microtime(true) + 20;

    while (! is_file($path)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out while waiting for the outgoing-number concurrency release signal.');
        }

        usleep(10_000);
    }
};

try {
    file_put_contents($readyPath, 'ready', LOCK_EX);
    $waitForRelease($releasePath);

    app(AssignOutgoingLetterNumber::class)->execute(
        User::query()->findOrFail((int) $userId),
        OutgoingLetter::query()->findOrFail((int) $outgoingLetterId),
        $number,
        $letterDate,
    );

    echo json_encode(['status' => 200], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['status' => 422], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode([
        'status' => 500,
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
