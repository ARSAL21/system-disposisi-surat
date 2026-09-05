<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argc !== 3 || ! in_array($argv[1], ['create', 'drop'], true)) {
    fwrite(STDERR, "Usage: php ManageMySqlConcurrencyDatabase.php <create|drop> <database>\n");
    exit(2);
}

[, $operation, $databaseName] = $argv;

if (preg_match('/^disposisi_surat_concurrency_test_[a-z0-9]+$/', $databaseName) !== 1) {
    fwrite(STDERR, "Refusing to manage a database outside the concurrency-test naming boundary.\n");
    exit(3);
}

$connection = DB::connection();

if ($connection->getDriverName() !== 'mysql') {
    fwrite(STDERR, "The concurrency database manager requires a MySQL connection.\n");
    exit(4);
}

if ($connection->getDatabaseName() === $databaseName) {
    fwrite(STDERR, "Refusing to manage the application's currently selected database.\n");
    exit(5);
}

$exists = DB::selectOne(
    'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
    [$databaseName],
) !== null;
$quotedDatabaseName = '`'.$databaseName.'`';

if ($operation === 'create') {
    if ($exists) {
        fwrite(STDERR, "Refusing to reuse an existing concurrency-test database.\n");
        exit(6);
    }

    DB::statement(
        "CREATE DATABASE {$quotedDatabaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    );
    fwrite(STDOUT, "created\n");

    exit(0);
}

if (! $exists) {
    fwrite(STDOUT, "already-absent\n");

    exit(0);
}

DB::statement("DROP DATABASE {$quotedDatabaseName}");
fwrite(STDOUT, "dropped\n");
