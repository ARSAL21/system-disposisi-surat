<?php

use App\Actions\RegisterIncomingLetter;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Exceptions\DocumentIntegrityConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\SenderOrganization;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\DocumentIntegrityResult;
use App\Services\DocumentIntegrityVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
    Storage::fake('public');
    $this->verifier = new DocumentIntegrityVerifier;
});

function createVerifiedSubmissionDocument(string $content = '%PDF-1.4 sample valid content'): array
{
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::ReadyForApproval;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'PT Verifikasi Hash';
    $submission->contact_name = 'Contact Person';
    $submission->contact_email = 'contact@example.com';
    $submission->subject = 'Uji Verifikasi Kriptografi';
    $submission->submitted_at = now();
    $submission->save();

    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $content);

    $document = new SubmissionDocument;
    $document->letter_submission_id = $submission->getKey();
    $document->storage_disk = 'submission-documents';
    $document->storage_path = $path;
    $document->original_filename = 'dokumen-uji.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($content);
    $document->sha256 = hash('sha256', $content);
    $document->uploaded_by_user_id = $owner->getKey();
    $document->save();

    return [$submission, $document];
}

function createKabagUser(): User
{
    $user = User::factory()->internal()->withTwoFactor()->create();

    $decidePermission = Permission::findOrCreate(PermissionName::DecideIntake->value, AuthorizationCatalog::GUARD_NAME);
    $role = Role::findOrCreate('kabag-verifier-role', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo($decidePermission);
    $user->assignRole($role);

    $level = PositionLevel::where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = OrganizationCatalog::SECTION_HEAD_LEVEL;
        $level->name = 'Section Head Level';
        $level->hierarchy_order = 40;
        $level->is_active = true;
        $level->save();
    }

    $unit = OrganizationalUnit::where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)->first();
    if (! $unit instanceof OrganizationalUnit) {
        $unit = new OrganizationalUnit;
        $unit->code = OrganizationCatalog::GENERAL_AFFAIRS_UNIT;
        $unit->name = 'Bagian Umum';
        $unit->is_active = true;
        $unit->save();
    }

    $position = new Position;
    $position->position_level_id = $level->id;
    $position->organizational_unit_id = $unit->id;
    $position->code = 'POS_KABAG_VERIFY_'.Str::upper(Str::random(6));
    $position->name = 'Kepala Bagian Umum Verifier';
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->id;
    $assignment->position_id = $position->id;
    $assignment->started_at = now()->subHour();
    $assignment->ended_at = null;
    $assignment->save();

    return $user;
}

it('verifies that letter-documents disk is properly configured in config/filesystems.php', function (): void {
    $config = config('filesystems.disks.letter-documents');

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('local')
        ->and($config['visibility'])->toBe('private')
        ->and($config['throw'])->toBeTrue()
        ->and($config['report'])->toBeTrue();
});

it('verifies an intact submission document and returns MATCH status', function (): void {
    [, $document] = createVerifiedSubmissionDocument();

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeTrue()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_MATCH)
        ->and($result->expectedHash)->toBe(strtolower($document->sha256))
        ->and($result->actualHash)->toBe(strtolower($document->sha256))
        ->and($result->actualBytes)->toBe($document->size_bytes);

    $this->verifier->verifyOrFail($document);
});

it('detects file tampering and throws fingerprintMismatch on SHA-256 discrepancy', function (): void {
    [, $document] = createVerifiedSubmissionDocument('Original content before tampering');

    // Tamper with the physical file in storage (change 1 character)
    Storage::disk('submission-documents')->put($document->storage_path, 'Modified content before tampering');

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_HASH_MISMATCH)
        ->and($result->expectedHash)->toBe(strtolower($document->sha256))
        ->and($result->actualHash)->not->toBe(strtolower($document->sha256));

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Integritas dokumen tidak dapat diverifikasi (sidik jari atau ukuran berkas tidak cocok).');
});

it('detects size discrepancy when bytes are truncated or appended', function (): void {
    [, $document] = createVerifiedSubmissionDocument('Exact initial content');

    // Append extra bytes to file
    Storage::disk('submission-documents')->put($document->storage_path, 'Exact initial content extra appended data');

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_SIZE_MISMATCH)
        ->and($result->actualBytes)->not->toBe($document->size_bytes);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class);
});

it('detects invalid or corrupted database metadata such as non-hex sha256 or negative size', function (string $sha256, int $sizeBytes, string $mimeType): void {
    [, $document] = createVerifiedSubmissionDocument();
    $document->sha256 = $sha256;
    $document->size_bytes = $sizeBytes;
    $document->mime_type = $mimeType;

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_INVALID_METADATA);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Metadata integritas dokumen pada basis data tidak valid.');
})->with([
    ['not-a-valid-64-hex-sha256', 50, 'application/pdf'],
    [str_repeat('z', 64), 50, 'application/pdf'],
    [hash('sha256', 'valid'), -10, 'application/pdf'],
    [hash('sha256', 'valid'), 50, 'image/jpeg'],
]);

it('enforces submission public_id prefix even when relation is not eager loaded', function (): void {
    [$submission, $document] = createVerifiedSubmissionDocument();

    // Set path to another submission's public_id prefix
    $otherPublicId = (string) Str::ulid();
    $tamperedPath = $otherPublicId.'/doc.pdf';
    Storage::disk('submission-documents')->put($tamperedPath, '%PDF-1.4 other content');

    // Retrieve fresh document model from database without eager-loading submission
    $freshDoc = SubmissionDocument::query()->find($document->id);
    $freshDoc->storage_path = $tamperedPath;
    $freshDoc->save();

    $result = $this->verifier->inspect($freshDoc);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_INVALID_PATH);

    expect(fn () => $this->verifier->verifyOrFail($freshDoc))
        ->toThrow(DocumentIntegrityConflict::class, 'Lokasi dokumen tidak valid.');
});

it('fails verification with invalid metadata when submission relation is missing for a SubmissionDocument', function (): void {
    [, $document] = createVerifiedSubmissionDocument();

    // Point letter_submission_id to non-existent ID
    $document->letter_submission_id = 999999;

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_INVALID_METADATA);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Metadata integritas dokumen pada basis data tidak valid.');
});

it('detects missing physical file on private storage disk', function (): void {
    [, $document] = createVerifiedSubmissionDocument();

    // Physically delete file
    Storage::disk('submission-documents')->delete($document->storage_path);

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_FILE_UNAVAILABLE);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Dokumen sumber tidak tersedia atau tidak dapat dibaca pada penyimpanan privat.');
});

it('detects invalid or malicious storage path with directory traversal or null bytes', function (string $invalidPath): void {
    [, $document] = createVerifiedSubmissionDocument();
    $document->storage_path = $invalidPath;

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_INVALID_PATH);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Lokasi dokumen tidak valid.');
})->with([
    '../traversal/doc.pdf',
    "public_id/\0nullbyte.pdf",
    '/absolute/root/doc.pdf',
    'C:/Windows/doc.pdf',
    'public_id/wrong_ext.docx',
]);

it('detects disallowed storage disk', function (): void {
    [, $document] = createVerifiedSubmissionDocument();
    $document->storage_disk = 'public';

    $result = $this->verifier->inspect($document);

    expect($result->isValid())->toBeFalse()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_INVALID_DISK);

    expect(fn () => $this->verifier->verifyOrFail($document))
        ->toThrow(DocumentIntegrityConflict::class, 'Penyimpanan dokumen tidak valid.');
});

it('verifies integrity of official LetterDocument stored in letter-documents disk', function (): void {
    $content = '%PDF-1.4 official registered letter document';
    $path = 'letters/2026/agenda-001.pdf';
    Storage::disk('letter-documents')->put($path, $content);

    $letterDoc = new LetterDocument;
    $letterDoc->incoming_letter_id = 1;
    $letterDoc->source_submission_document_id = null;
    $letterDoc->version_number = 1;
    $letterDoc->replaces_document_id = null;
    $letterDoc->storage_disk = 'letter-documents';
    $letterDoc->storage_path = $path;
    $letterDoc->original_filename = 'surat-resmi.pdf';
    $letterDoc->mime_type = 'application/pdf';
    $letterDoc->size_bytes = strlen($content);
    $letterDoc->sha256 = hash('sha256', $content);
    $letterDoc->uploaded_by_user_id = 1;

    $result = $this->verifier->inspect($letterDoc);

    expect($result->isValid())->toBeTrue()
        ->and($result->status)->toBe(DocumentIntegrityResult::STATUS_MATCH);
});

it('rolls back database transaction cleanly when document tampering is detected during letter registration', function (): void {
    $kabag = createKabagUser();
    [$submission, $document] = createVerifiedSubmissionDocument('Secret government letter payload');

    // Tamper with file before Kabag executes registration
    Storage::disk('submission-documents')->put($document->storage_path, 'TAMPERED AND CORRUPTED CONTENT');

    $senderOrg = new SenderOrganization;
    $senderOrg->name = 'Instansi Pengirim';
    $senderOrg->save();

    expect(function () use ($kabag, $submission, $senderOrg): void {
        app(RegisterIncomingLetter::class)->execute($kabag, $submission, [
            'agenda_number' => '001/TEST/2026',
            'note' => 'Catatan registrasi',
            'sender_organization' => [
                'mode' => 'existing',
                'id' => $senderOrg->id,
            ],
        ]);
    })->toThrow(DocumentIntegrityConflict::class, 'Integritas dokumen tidak dapat diverifikasi (sidik jari atau ukuran berkas tidak cocok).');

    // Assert that IncomingLetter and LetterDocument were NOT persisted
    expect(IncomingLetter::where('agenda_number', '001/TEST/2026')->exists())->toBeFalse()
        ->and(LetterDocument::count())->toBe(0)
        ->and($submission->fresh()->status)->toBe(SubmissionStatus::ReadyForApproval);
});

it('runs documents:verify-integrity CLI command and succeeds when all documents are intact', function (): void {
    createVerifiedSubmissionDocument('File 1 content');
    createVerifiedSubmissionDocument('File 2 content');

    $this->artisan('documents:verify-integrity', ['--all' => true])
        ->expectsOutputToContain('Starting document cryptographic integrity scan...')
        ->expectsOutputToContain('Total Scanned: 2')
        ->expectsOutputToContain('Passed: 2')
        ->expectsOutputToContain('All scanned documents successfully verified.')
        ->assertExitCode(0);
});

it('runs documents:verify-integrity CLI command and rejects non-positive limit', function (string $invalidLimit): void {
    $this->artisan('documents:verify-integrity', ['--limit' => $invalidLimit])
        ->expectsOutputToContain('The --limit option must be a positive integer greater than 0.')
        ->assertExitCode(1);
})->with(['0', '-5', 'invalid']);

it('runs documents:verify-integrity CLI command and honors positive limit', function (): void {
    createVerifiedSubmissionDocument('File 1');
    createVerifiedSubmissionDocument('File 2');
    createVerifiedSubmissionDocument('File 3');

    $this->artisan('documents:verify-integrity', ['--limit' => '1'])
        ->expectsOutputToContain('Total Scanned: 1')
        ->expectsOutputToContain('Passed: 1')
        ->assertExitCode(0);
});

it('runs documents:verify-integrity CLI command and reports failure on tampered document', function (): void {
    [, $doc1] = createVerifiedSubmissionDocument('Intact file 1');
    [, $doc2] = createVerifiedSubmissionDocument('Intact file 2');

    // Tamper doc2
    Storage::disk('submission-documents')->put($doc2->storage_path, 'CORRUPTED FILE 2');

    $this->artisan('documents:verify-integrity', ['--all' => true])
        ->expectsOutputToContain('Total Scanned: 2')
        ->expectsOutputToContain('Passed: 1')
        ->expectsOutputToContain('Failed: 1')
        ->expectsOutputToContain('Integrity check failed.')
        ->assertExitCode(1);
});

it('stops on first failure when using --fail-fast in documents:verify-integrity command', function (): void {
    [, $doc1] = createVerifiedSubmissionDocument('File 1');
    [, $doc2] = createVerifiedSubmissionDocument('File 2');

    // Tamper doc1
    Storage::disk('submission-documents')->put($doc1->storage_path, 'CORRUPTED FILE 1');

    $this->artisan('documents:verify-integrity', ['--fail-fast' => true])
        ->expectsOutputToContain('Fail-fast triggered')
        ->assertExitCode(1);
});

it('sanitizes control characters in CLI report when database strings contain ANSI escape or control codes', function (): void {
    [, $doc] = createVerifiedSubmissionDocument('Corrupt document payload');

    $doc->storage_disk = "submission\x1b[31m\x00-documents";
    $doc->save();

    $this->artisan('documents:verify-integrity', ['--all' => true])
        ->expectsOutputToContain('Total Scanned: 1')
        ->assertExitCode(1);
});
