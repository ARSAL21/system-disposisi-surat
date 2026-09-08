<?php

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\SenderOrganization;
use App\Models\User;
use Database\Seeders\OrganizationAndUserSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(env('RUN_MYSQL_CONCURRENCY_TESTS', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('Set RUN_MYSQL_CONCURRENCY_TESTS=true on an isolated MySQL database.');
    }

    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL concurrency tests require the mysql database driver.');
    }

    $databaseName = DB::connection()->getDatabaseName();

    if (preg_match('/^disposisi_surat_concurrency_test(?:_[a-z0-9]+)?$/', $databaseName) !== 1) {
        throw new RuntimeException(
            'Refusing to migrate a database that is not explicitly named for disposition concurrency testing.',
        );
    }

    expect(Artisan::call('migrate:fresh', ['--force' => true]))->toBe(0);
    $this->seed(OrganizationAndUserSeeder::class);
});

/** @return array{letter: IncomingLetter, branches: array<string, DispositionRecipient>} */
function mysqlConcurrencyDispositionGraph(): array
{
    $registrar = mysqlConcurrencyUser('kabag.umum@internal.test');
    $registrarAssignment = mysqlConcurrencyAssignment($registrar, mysqlConcurrencyPosition('KABAG_UMUM'));
    $executivePosition = mysqlConcurrencyPosition('SEKDA');
    $executiveAssignment = PositionAssignment::query()
        ->where('position_id', $executivePosition->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
    $assistantPosition = mysqlConcurrencyPosition('ASISTEN-I');
    $assistantAssignment = PositionAssignment::query()
        ->where('position_id', $assistantPosition->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
    $receivedAt = now()->subDay();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Manual;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = null;
    $submission->recorded_by_user_id = $registrar->getKey();
    $submission->sender_organization_name = 'Instansi Uji Konkurensi';
    $submission->contact_name = 'Petugas Pengujian';
    $submission->contact_email = 'concurrency@example.test';
    $submission->external_letter_number = 'CONCURRENCY/'.Str::upper(Str::random(10));
    $submission->external_letter_date = $receivedAt->toDateString();
    $submission->subject = 'Pengujian penyelesaian cabang bersamaan';
    $submission->summary = 'Fixture terisolasi untuk verifikasi row lock MySQL.';
    $submission->submitted_at = $receivedAt;
    $submission->save();

    $sender = new SenderOrganization;
    $sender->name = 'Instansi Uji Konkurensi';
    $sender->is_active = true;
    $sender->save();

    $letter = new IncomingLetter;
    $letter->letter_submission_id = $submission->getKey();
    $letter->agenda_number = 'CONCURRENCY/'.Str::upper(Str::random(10));
    $letter->agenda_year = (int) now()->format('Y');
    $letter->sender_organization_id = $sender->getKey();
    $letter->external_letter_number = $submission->external_letter_number;
    $letter->external_letter_date = $submission->external_letter_date;
    $letter->subject = $submission->subject;
    $letter->summary = $submission->summary;
    $letter->received_at = $receivedAt;
    $letter->status = IncomingLetterStatus::InProgress;
    $letter->registered_by_user_id = $registrar->getKey();
    $letter->registered_by_position_assignment_id = $registrarAssignment->getKey();
    $letter->save();

    $route = new LetterRoute;
    $route->incoming_letter_id = $letter->getKey();
    $route->recipient_position_id = $executivePosition->getKey();
    $route->routed_by_user_id = $registrar->getKey();
    $route->routed_by_position_assignment_id = $registrarAssignment->getKey();
    $route->status = LetterRouteStatus::Completed;
    $route->routed_at = $receivedAt->addHour();
    $route->completed_at = $receivedAt->addHours(2);
    $route->save();

    $initialDisposition = new Disposition;
    $initialDisposition->incoming_letter_id = $letter->getKey();
    $initialDisposition->source_route_id = $route->getKey();
    $initialDisposition->parent_recipient_id = null;
    $initialDisposition->created_by_user_id = $executiveAssignment->user_id;
    $initialDisposition->created_by_position_assignment_id = $executiveAssignment->getKey();
    $initialDisposition->instruction_note = 'Teruskan sesuai kewenangan.';
    $initialDisposition->created_at = $receivedAt->addHours(2);
    $initialDisposition->save();
    $initialDisposition->instructionLabels()->attach(
        InstructionLabel::query()->where('code', 'FOLLOW_UP')->valueOrFail('id'),
    );

    $assistantRecipient = mysqlConcurrencyPendingRecipient(
        $initialDisposition,
        $assistantPosition,
        $receivedAt->addHours(2),
    );

    $childDisposition = new Disposition;
    $childDisposition->incoming_letter_id = $letter->getKey();
    $childDisposition->source_route_id = null;
    $childDisposition->parent_recipient_id = $assistantRecipient->getKey();
    $childDisposition->created_by_user_id = $assistantAssignment->user_id;
    $childDisposition->created_by_position_assignment_id = $assistantAssignment->getKey();
    $childDisposition->instruction_note = 'Selesaikan pada cabang masing-masing.';
    $childDisposition->created_at = $receivedAt->addHours(3);
    $childDisposition->save();

    $assistantRecipient->status = DispositionRecipientStatus::Completed;
    $assistantRecipient->completed_at = $receivedAt->addHours(3);
    $assistantRecipient->completed_by_user_id = $assistantAssignment->user_id;
    $assistantRecipient->completed_by_position_assignment_id = $assistantAssignment->getKey();
    $assistantRecipient->completion_note = null;
    $assistantRecipient->save();

    return [
        'letter' => $letter,
        'branches' => [
            'KABAG_KESRA' => mysqlConcurrencyPendingRecipient(
                $childDisposition,
                mysqlConcurrencyPosition('KABAG_KESRA'),
                $receivedAt->addHours(3),
            ),
            'KABAG_TAPEM' => mysqlConcurrencyPendingRecipient(
                $childDisposition,
                mysqlConcurrencyPosition('KABAG_TAPEM'),
                $receivedAt->addHours(3),
            ),
        ],
    ];
}

function mysqlConcurrencyPendingRecipient(
    Disposition $disposition,
    Position $position,
    DateTimeInterface $receivedAt,
): DispositionRecipient {
    $recipient = new DispositionRecipient;
    $recipient->disposition_id = $disposition->getKey();
    $recipient->recipient_position_id = $position->getKey();
    $recipient->status = DispositionRecipientStatus::Pending;
    $recipient->received_at = $receivedAt;
    $recipient->started_at = null;
    $recipient->completed_at = null;
    $recipient->completed_by_user_id = null;
    $recipient->completed_by_position_assignment_id = null;
    $recipient->completion_note = null;
    $recipient->save();

    return $recipient;
}

function mysqlConcurrencyUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

function mysqlConcurrencyPosition(string $code): Position
{
    return Position::query()->where('code', $code)->firstOrFail();
}

function mysqlConcurrencyAssignment(User $user, Position $position): PositionAssignment
{
    return PositionAssignment::query()
        ->where('user_id', $user->getKey())
        ->where('position_id', $position->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
}

/**
 * @return array{0: array{status: int}, 1: array{status: int}}
 */
function mysqlConcurrencyRace(
    IncomingLetter $letter,
    User $holdingActor,
    DispositionRecipient $holdingBranch,
    User $waitingActor,
    DispositionRecipient $waitingBranch,
): array {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'disposition-concurrency-'.Str::uuid();

    if (! File::makeDirectory($directory, 0700, true)) {
        throw new RuntimeException('Unable to create the concurrency signal directory.');
    }

    $lockedSignal = $directory.DIRECTORY_SEPARATOR.'holding-worker-locked';
    $waitingSignal = $directory.DIRECTORY_SEPARATOR.'waiting-worker-attempting';
    $releaseSignal = $directory.DIRECTORY_SEPARATOR.'release-holding-worker';
    $holdingProcess = mysqlConcurrencyWorker(
        'hold',
        $holdingActor,
        $holdingBranch,
        $letter,
        $lockedSignal,
        $releaseSignal,
    );
    $waitingProcess = null;

    try {
        mysqlConcurrencyWaitForSignal($lockedSignal, $holdingProcess);

        $waitingProcess = mysqlConcurrencyWorker(
            'normal',
            $waitingActor,
            $waitingBranch,
            $letter,
            $waitingSignal,
            $releaseSignal,
        );
        mysqlConcurrencyWaitForSignal($waitingSignal, $waitingProcess);
        usleep(250_000);
        file_put_contents($releaseSignal, 'release', LOCK_EX);

        return [
            mysqlConcurrencyWorkerResult($holdingProcess),
            mysqlConcurrencyWorkerResult($waitingProcess),
        ];
    } finally {
        if ($holdingProcess->isRunning()) {
            $holdingProcess->stop();
        }

        if ($waitingProcess?->isRunning()) {
            $waitingProcess->stop();
        }

        File::deleteDirectory($directory);
    }
}

function mysqlConcurrencyWorker(
    string $mode,
    User $actor,
    DispositionRecipient $branch,
    IncomingLetter $letter,
    string $signalPath,
    string $releasePath,
): Process {
    $process = new Process([
        PHP_BINARY,
        base_path('tests/Support/RunDispositionCompletionWorker.php'),
        $mode,
        (string) $actor->getKey(),
        (string) $branch->getKey(),
        (string) $letter->getKey(),
        $signalPath,
        $releasePath,
    ], base_path(), mysqlConcurrencyProcessEnvironment(), null, 30);
    $process->start();

    return $process;
}

/** @return array<string, string> */
function mysqlConcurrencyProcessEnvironment(): array
{
    $connection = config('database.connections.mysql');

    return [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'APP_DEBUG' => 'false',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => (string) ($connection['host'] ?? '127.0.0.1'),
        'DB_PORT' => (string) ($connection['port'] ?? '3306'),
        'DB_DATABASE' => (string) DB::connection()->getDatabaseName(),
        'DB_USERNAME' => (string) ($connection['username'] ?? ''),
        'DB_PASSWORD' => (string) ($connection['password'] ?? ''),
        'DB_SOCKET' => (string) ($connection['unix_socket'] ?? ''),
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ];
}

function mysqlConcurrencyWaitForSignal(string $path, Process $process): void
{
    $deadline = microtime(true) + 20;

    while (! is_file($path)) {
        if (! $process->isRunning()) {
            throw new RuntimeException('Concurrency worker stopped before signalling: '.$process->getErrorOutput());
        }

        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out while waiting for a concurrency worker signal.');
        }

        usleep(10_000);
    }
}

/** @return array{status: int} */
function mysqlConcurrencyWorkerResult(Process $process): array
{
    $exitCode = $process->wait();
    $output = trim($process->getOutput());

    if ($exitCode !== 0 || $output === '') {
        throw new RuntimeException('Concurrency worker failed: '.$process->getErrorOutput());
    }

    $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($result) || ! is_int($result['status'] ?? null)) {
        throw new RuntimeException('Concurrency worker returned an invalid result: '.$output);
    }

    return ['status' => $result['status']];
}

function mysqlConcurrencyAuthorizedOutgoingLetter(
    IncomingLetter $letter,
    User $authorizer,
    PositionAssignment $authorizerAssignment,
): OutgoingLetter {
    $now = now();
    $dossierId = DB::table('letter_response_dossiers')->insertGetId([
        'public_id' => (string) Str::ulid(),
        'incoming_letter_id' => $letter->getKey(),
        'status' => LetterResponseDossierStatus::Finalized->value,
        'opened_at' => $now,
        'finalized_at' => $now,
        'finalized_by_user_id' => $authorizer->getKey(),
        'finalized_by_position_assignment_id' => $authorizerAssignment->getKey(),
        'fulfilled_at' => null,
        'fulfilled_by_user_id' => null,
        'fulfilled_by_position_assignment_id' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $documentId = DB::table('letter_response_documents')->insertGetId([
        'public_id' => (string) Str::ulid(),
        'letter_response_dossier_id' => $dossierId,
        'kind' => 'EXECUTIVE_CONSOLIDATION',
        'owner_position_id' => $authorizerAssignment->position_id,
        'source_recipient_id' => null,
        'created_by_user_id' => $authorizer->getKey(),
        'created_by_position_assignment_id' => $authorizerAssignment->getKey(),
        'created_at' => $now,
    ]);
    $versionId = DB::table('letter_response_document_versions')->insertGetId([
        'public_id' => (string) Str::ulid(),
        'letter_response_document_id' => $documentId,
        'version_number' => 1,
        'replaces_version_id' => null,
        'storage_disk' => 'letter-response-documents',
        'storage_path' => 'mysql-concurrency/'.Str::ulid().'.pdf',
        'original_filename' => 'konsolidasi.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1,
        'sha256' => hash('sha256', (string) Str::ulid()),
        'revision_note' => null,
        'uploaded_by_user_id' => $authorizer->getKey(),
        'uploaded_by_position_assignment_id' => $authorizerAssignment->getKey(),
        'created_at' => $now,
    ]);
    $outgoingId = DB::table('outgoing_letters')->insertGetId([
        'public_id' => (string) Str::ulid(),
        'incoming_letter_id' => $letter->getKey(),
        'letter_response_dossier_id' => $dossierId,
        'source_document_version_id' => $versionId,
        'signatory_position_id' => $authorizerAssignment->position_id,
        'subject' => 'Mandat nomor surat untuk uji konkurensi',
        'status' => OutgoingLetterStatus::Authorized->value,
        'authorized_by_user_id' => $authorizer->getKey(),
        'authorized_by_position_assignment_id' => $authorizerAssignment->getKey(),
        'authorized_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return OutgoingLetter::query()->findOrFail($outgoingId);
}

/** @return array{0: array{status: int}, 1: array{status: int}} */
function mysqlOutgoingNumberRace(User $actor, OutgoingLetter $first, OutgoingLetter $second): array
{
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'outgoing-number-concurrency-'.Str::uuid();

    if (! File::makeDirectory($directory, 0700, true)) {
        throw new RuntimeException('Unable to create the outgoing-number concurrency signal directory.');
    }

    $releaseSignal = $directory.DIRECTORY_SEPARATOR.'release-workers';
    $firstReady = $directory.DIRECTORY_SEPARATOR.'first-ready';
    $secondReady = $directory.DIRECTORY_SEPARATOR.'second-ready';
    $arguments = fn (OutgoingLetter $letter, string $readySignal): array => [
        PHP_BINARY,
        base_path('tests/Support/RunOutgoingLetterNumberWorker.php'),
        (string) $actor->getKey(),
        (string) $letter->getKey(),
        '009/1201/SETDA/2026',
        '2026-09-09',
        $readySignal,
        $releaseSignal,
    ];
    $firstWorker = new Process($arguments($first, $firstReady), base_path(), mysqlConcurrencyProcessEnvironment(), null, 30);
    $secondWorker = new Process($arguments($second, $secondReady), base_path(), mysqlConcurrencyProcessEnvironment(), null, 30);
    $firstWorker->start();
    $secondWorker->start();

    try {
        mysqlConcurrencyWaitForSignal($firstReady, $firstWorker);
        mysqlConcurrencyWaitForSignal($secondReady, $secondWorker);
        file_put_contents($releaseSignal, 'release', LOCK_EX);

        return [
            mysqlConcurrencyWorkerResult($firstWorker),
            mysqlConcurrencyWorkerResult($secondWorker),
        ];
    } finally {
        if ($firstWorker->isRunning()) {
            $firstWorker->stop();
        }

        if ($secondWorker->isRunning()) {
            $secondWorker->stop();
        }

        File::deleteDirectory($directory);
    }
}

test('two contending final branches complete the letter with one aggregate audit', function (): void {
    $graph = mysqlConcurrencyDispositionGraph();
    $firstBranch = $graph['branches']['KABAG_KESRA'];
    $secondBranch = $graph['branches']['KABAG_TAPEM'];
    $results = mysqlConcurrencyRace(
        $graph['letter'],
        mysqlConcurrencyUser('kabag.kesra@internal.test'),
        $firstBranch,
        mysqlConcurrencyUser('kabag.tapem@internal.test'),
        $secondBranch,
    );

    expect(array_column($results, 'status'))->toBe([200, 200])
        ->and($graph['letter']->refresh()->status)->toBe(IncomingLetterStatus::Completed)
        ->and($firstBranch->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($secondBranch->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and(AuditLog::query()
            ->where('action', AuditAction::LetterCompleted->value)
            ->where('subject_type', 'incoming_letter')
            ->where('subject_id', $graph['letter']->getKey())
            ->count())->toBe(1);
})->group('mysql-concurrency');

test('two contending completions of one branch yield one success and one conflict', function (): void {
    $graph = mysqlConcurrencyDispositionGraph();
    $branch = $graph['branches']['KABAG_KESRA'];
    $actor = mysqlConcurrencyUser('kabag.kesra@internal.test');
    $results = mysqlConcurrencyRace(
        $graph['letter'],
        $actor,
        $branch,
        $actor,
        $branch,
    );
    $statuses = array_column($results, 'status');
    sort($statuses);

    expect($statuses)->toBe([200, 409])
        ->and($branch->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($graph['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and(AuditLog::query()
            ->where('action', AuditAction::DispositionCompleted->value)
            ->where('subject_type', 'disposition_recipient')
            ->where('subject_id', $branch->getKey())
            ->count())->toBe(1)
        ->and(AuditLog::query()
            ->where('action', AuditAction::LetterCompleted->value)
            ->where('subject_type', 'incoming_letter')
            ->where('subject_id', $graph['letter']->getKey())
            ->count())->toBe(0);
})->group('mysql-concurrency');

test('two competing outgoing number assignments retain one unique registration', function (): void {
    $firstGraph = mysqlConcurrencyDispositionGraph();
    $secondGraph = mysqlConcurrencyDispositionGraph();
    $authorizer = mysqlConcurrencyUser('sekda@internal.test');
    $authorizerAssignment = mysqlConcurrencyAssignment($authorizer, mysqlConcurrencyPosition('SEKDA'));
    $officer = mysqlConcurrencyUser('petugas.surat@internal.test');
    $firstOutgoing = mysqlConcurrencyAuthorizedOutgoingLetter(
        $firstGraph['letter'],
        $authorizer,
        $authorizerAssignment,
    );
    $secondOutgoing = mysqlConcurrencyAuthorizedOutgoingLetter(
        $secondGraph['letter'],
        $authorizer,
        $authorizerAssignment,
    );

    $statuses = array_column(mysqlOutgoingNumberRace($officer, $firstOutgoing, $secondOutgoing), 'status');
    sort($statuses);

    expect($statuses)->toBe([200, 422])
        ->and(OutgoingLetter::query()
            ->where('agenda_year', 2026)
            ->where('outgoing_number', '009/1201/SETDA/2026')
            ->count())->toBe(1)
        ->and(OutgoingLetter::query()
            ->whereIn('id', [$firstOutgoing->getKey(), $secondOutgoing->getKey()])
            ->where('status', OutgoingLetterStatus::NumberAssigned->value)
            ->count())->toBe(1)
        ->and(AuditLog::query()
            ->where('action', AuditAction::OutgoingLetterNumberAssigned->value)
            ->count())->toBe(1);
})->group('mysql-concurrency');
