<?php

use App\Actions\CreateLetterDocumentVersion;
use App\Actions\RecordAudit;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
});

afterEach(function (): void {
    Date::setTestNow();
});

function documentVersionLevel(string $code): PositionLevel
{
    $existing = PositionLevel::query()->where('code', $code)->first();

    if ($existing instanceof PositionLevel) {
        return $existing;
    }

    $level = new PositionLevel;
    $level->code = $code;
    $level->name = str_replace('_', ' ', $code);
    $level->hierarchy_order = match ($code) {
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL => 10,
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL => 20,
        'ASSISTANT' => 30,
        OrganizationCatalog::SECTION_HEAD_LEVEL => 40,
        default => 90,
    };
    $level->is_active = true;
    $level->save();

    return $level;
}

function documentVersionUnit(string $code): OrganizationalUnit
{
    $existing = OrganizationalUnit::query()->where('code', $code)->first();

    if ($existing instanceof OrganizationalUnit) {
        return $existing;
    }

    $unit = new OrganizationalUnit;
    $unit->code = $code;
    $unit->name = str_replace('_', ' ', $code);
    $unit->is_active = true;
    $unit->save();

    return $unit;
}

function documentVersionAssignment(
    User $user,
    string $levelCode,
    ?string $unitCode = null,
): PositionAssignment {
    $position = new Position;
    $position->position_level_id = documentVersionLevel($levelCode)->getKey();
    $position->organizational_unit_id = $unitCode === null
        ? null
        : documentVersionUnit($unitCode)->getKey();
    $position->code = 'DOC-'.Str::upper(Str::random(12));
    $position->name = match ($levelCode) {
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL => 'Staf Administrasi Surat',
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL => 'Sekretaris Daerah',
        OrganizationCatalog::SECTION_HEAD_LEVEL => 'Kepala Bagian',
        default => 'Asisten',
    };
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $assignment;
}

function documentVersionGrant(User $user, PermissionName ...$permissions): void
{
    $permissionModels = collect($permissions)->map(
        fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
    );
    $role = Role::findOrCreate('document-role-'.Str::lower(Str::random(10)));
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);
}

function documentVersionActor(
    string $levelCode,
    ?string $unitCode,
    PermissionName ...$permissions,
): User {
    $user = User::factory()->internal()->create();
    documentVersionGrant($user, ...$permissions);
    documentVersionAssignment($user, $levelCode, $unitCode);

    return $user;
}

/**
 * @return array{letter: IncomingLetter, initial: LetterDocument, registrar: User, registrar_assignment: PositionAssignment}
 */
function documentVersionLetter(
    string $subject = 'Surat pengujian versi dokumen',
    IncomingLetterStatus $status = IncomingLetterStatus::Registered,
    string $initialContents = '%PDF-1.4 initial official document',
    ?DateTimeInterface $receivedAt = null,
    ?DateTimeInterface $documentCreatedAt = null,
): array {
    $owner = User::factory()->create();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Instansi '.Str::random(8);
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->external_letter_number = 'EXT/'.Str::upper(Str::random(8));
    $submission->external_letter_date = '2026-08-01';
    $submission->subject = $subject;
    $submission->summary = 'Ringkasan surat pengujian.';
    $submission->submitted_at = now()->subDays(2);
    $submission->save();

    $initialPath = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($initialPath, $initialContents);

    $sourceDocument = new SubmissionDocument;
    $sourceDocument->letter_submission_id = $submission->getKey();
    $sourceDocument->storage_disk = 'submission-documents';
    $sourceDocument->storage_path = $initialPath;
    $sourceDocument->original_filename = 'surat-awal-'.Str::lower(Str::random(6)).'.pdf';
    $sourceDocument->mime_type = 'application/pdf';
    $sourceDocument->size_bytes = strlen($initialContents);
    $sourceDocument->sha256 = hash('sha256', $initialContents);
    $sourceDocument->uploaded_by_user_id = $owner->getKey();
    $sourceDocument->save();

    $registrar = User::factory()->internal()->create();
    $registrarAssignment = documentVersionAssignment(
        $registrar,
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );

    $sender = new SenderOrganization;
    $sender->name = $submission->sender_organization_name;
    $sender->is_active = true;
    $sender->save();

    $letter = new IncomingLetter;
    $letter->letter_submission_id = $submission->getKey();
    $letter->agenda_number = 'AG-'.Str::upper(Str::random(10));
    $letter->agenda_year = 2026;
    $letter->sender_organization_id = $sender->getKey();
    $letter->external_letter_number = $submission->external_letter_number;
    $letter->external_letter_date = $submission->external_letter_date;
    $letter->subject = $subject;
    $letter->summary = $submission->summary;
    $letter->received_at = $receivedAt ?? now();
    $letter->status = $status;
    $letter->registered_by_user_id = $registrar->getKey();
    $letter->registered_by_position_assignment_id = $registrarAssignment->getKey();
    $letter->save();

    $initial = new LetterDocument;
    $initial->incoming_letter_id = $letter->getKey();
    $initial->source_submission_document_id = $sourceDocument->getKey();
    $initial->version_number = 1;
    $initial->replaces_document_id = null;
    $initial->storage_disk = $sourceDocument->storage_disk;
    $initial->storage_path = $sourceDocument->storage_path;
    $initial->original_filename = $sourceDocument->original_filename;
    $initial->mime_type = $sourceDocument->mime_type;
    $initial->size_bytes = $sourceDocument->size_bytes;
    $initial->sha256 = $sourceDocument->sha256;
    $initial->correction_reason = null;
    $initial->uploaded_by_user_id = $sourceDocument->uploaded_by_user_id;
    $initial->created_at = $documentCreatedAt ?? now();
    $initial->save();

    app(RecordAudit::class)->execute(
        actor: $registrar,
        action: AuditAction::DocumentVersionCreated,
        subjectType: 'letter_document',
        subjectId: $initial->getKey(),
        newValues: [
            'incoming_letter_id' => $letter->getKey(),
            'version_number' => 1,
            'sha256' => $initial->sha256,
        ],
        actorPositionAssignment: $registrarAssignment,
    );

    return [
        'letter' => $letter->refresh(),
        'initial' => $initial->refresh(),
        'registrar' => $registrar,
        'registrar_assignment' => $registrarAssignment,
    ];
}

function documentVersionCorrection(
    IncomingLetter $letter,
    LetterDocument $replaces,
    User $actor,
    PositionAssignment $assignment,
    string $filename = 'surat-koreksi.pdf',
    string $contents = '%PDF-1.4 corrected official document',
    ?DateTimeInterface $createdAt = null,
): LetterDocument {
    $path = 'letter-documents/'.$letter->getKey().'/'.Str::uuid().'.pdf';
    Storage::disk('letter-documents')->put($path, $contents);

    $document = new LetterDocument;
    $document->incoming_letter_id = $letter->getKey();
    $document->source_submission_document_id = null;
    $document->version_number = $replaces->version_number + 1;
    $document->replaces_document_id = $replaces->getKey();
    $document->storage_disk = 'letter-documents';
    $document->storage_path = $path;
    $document->original_filename = $filename;
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($contents);
    $document->sha256 = hash('sha256', $contents);
    $document->correction_reason = 'Koreksi administratif untuk dokumen resmi.';
    $document->uploaded_by_user_id = $actor->getKey();
    $document->created_at = $createdAt ?? now();
    $document->save();

    app(RecordAudit::class)->execute(
        actor: $actor,
        action: AuditAction::DocumentVersionCreated,
        subjectType: 'letter_document',
        subjectId: $document->getKey(),
        newValues: [
            'incoming_letter_id' => $letter->getKey(),
            'version_number' => $document->version_number,
            'sha256' => $document->sha256,
        ],
        actorPositionAssignment: $assignment,
    );

    return $document->refresh();
}

test('document archive permission and business position boundaries return 403 or 404', function (): void {
    $letter = documentVersionLetter()['letter'];

    $withoutPermission = User::factory()->internal()->create();
    documentVersionAssignment(
        $withoutPermission,
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );

    $this->actingAs($withoutPermission)
        ->get(route('back-office.documents.index'))
        ->assertForbidden();

    $assistant = documentVersionActor(
        'ASSISTANT',
        null,
        PermissionName::ViewDocumentVersions,
    );

    $this->actingAs($assistant)
        ->get(route('back-office.letters.documents.index', $letter))
        ->assertNotFound();

    $otherHead = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'BAGIAN_HUKUM',
        PermissionName::ViewDocumentVersions,
    );

    $this->actingAs($otherHead)
        ->get(route('back-office.documents.index'))
        ->assertNotFound();
});

test('general affairs staff, general affairs head, and executive entry can read the archive', function (): void {
    documentVersionLetter();

    $actors = [
        documentVersionActor(
            OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
            OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
            PermissionName::ViewDocumentVersions,
        ),
        documentVersionActor(
            OrganizationCatalog::SECTION_HEAD_LEVEL,
            OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
            PermissionName::ViewDocumentVersions,
        ),
        documentVersionActor(
            OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
            null,
            PermissionName::ViewDocumentVersions,
        ),
    ];

    foreach ($actors as $actor) {
        $this->actingAs($actor)
            ->get(route('back-office.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('back-office/documents/Index')
                ->has('documents.data', 1)
                ->where('auth.capabilities.can_view_document_versions', true));
    }
});

test('super admin permission does not bypass document archive position visibility', function (): void {
    $superAdmin = User::factory()->internal()->withTwoFactor()->create();
    $permissions = collect(AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin))
        ->map(fn (string $permission): Permission => Permission::findOrCreate($permission));
    $role = Role::findOrCreate(RoleName::SuperAdmin->value);
    $role->syncPermissions($permissions);
    $superAdmin->assignRole($role);

    $this->actingAs($superAdmin)
        ->get(route('back-office.documents.index'))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('back-office.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_document_versions', false));
});

test('archive filters all version filenames while summary remains unfiltered and current version uses maximum number', function (): void {
    Date::setTestNow('2026-08-30 04:00:00');

    $first = documentVersionLetter(
        subject: 'Surat pertama',
        receivedAt: now()->subDay(),
        documentCreatedAt: now()->subDay(),
    );
    $correctionActor = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );
    $correctionAssignment = $correctionActor->activePositionAssignments()->firstOrFail();
    $oldFilename = $first['initial']->original_filename;
    documentVersionCorrection(
        $first['letter'],
        $first['initial'],
        $correctionActor,
        $correctionAssignment,
        filename: 'versi-terkini.pdf',
    );

    documentVersionLetter(
        subject: 'Surat kedua',
        status: IncomingLetterStatus::Completed,
        receivedAt: now()->subDays(3),
        documentCreatedAt: now()->subDays(3),
    );

    $viewer = documentVersionActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
    );

    $this->actingAs($viewer)
        ->get(route('back-office.documents.index', [
            'search' => $oldFilename,
            'status' => IncomingLetterStatus::Registered->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1)
            ->where('documents.data.0.id', $first['letter']->getKey())
            ->where('documents.data.0.total_versions', 2)
            ->where('documents.data.0.current_version.version_number', 2)
            ->where('documents.data.0.current_version.original_filename', 'versi-terkini.pdf')
            ->where('summary.total_letters', 2)
            ->where('summary.corrected_letters', 1)
            ->where('summary.total_versions', 3)
            ->where('summary.updated_this_month', 2));
});

test('history returns every version newest first through an allowlist and separates uploader from historical recorder', function (): void {
    $fixture = documentVersionLetter();
    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );
    $assignment = $head->activePositionAssignments()->firstOrFail();
    $correction = documentVersionCorrection(
        $fixture['letter'],
        $fixture['initial'],
        $head,
        $assignment,
    );

    $this->actingAs($head)
        ->get(route('back-office.letters.documents.index', $fixture['letter']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/letters/documents/Index')
            ->has('versions', 2)
            ->where('versions.0.id', $correction->getKey())
            ->where('versions.0.version_number', 2)
            ->where('versions.0.is_current', true)
            ->where('versions.0.replaces_version_number', 1)
            ->where('versions.0.source', 'MANUAL_CORRECTION')
            ->where('versions.0.uploaded_by.name', $head->name)
            ->where('versions.0.recorded_by.name', $head->name)
            ->where('versions.0.recorded_by.position', 'Kepala Bagian')
            ->where('versions.1.source', 'ONLINE_SUBMISSION')
            ->where('versions.1.is_current', false)
            ->where('capabilities.can_create_version', true)
            ->where('next_version_number', 3)
            ->where('routes.store', route('back-office.letters.documents.store', $fixture['letter']))
            ->missing('versions.0.storage_disk')
            ->missing('versions.0.storage_path')
            ->missing('versions.0.uploaded_by.email')
            ->missing('versions.0.audit_metadata'));
});

test('preview and download stream each scoped version with private security headers', function (): void {
    $fixture = documentVersionLetter();
    $viewer = documentVersionActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
    );

    $preview = $this->actingAs($viewer)->get(route('back-office.letters.documents.preview', [
        'incomingLetter' => $fixture['letter'],
        'letterDocument' => $fixture['initial'],
    ]));

    $preview
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    expect($preview->headers->get('Content-Disposition'))->toStartWith('inline;');
    expect($preview->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store')
        ->toContain('max-age=0');

    $download = $this->actingAs($viewer)->get(route('back-office.letters.documents.download', [
        'incomingLetter' => $fixture['letter'],
        'letterDocument' => $fixture['initial'],
    ]));

    $download->assertOk();
    expect($download->headers->get('Content-Disposition'))->toStartWith('attachment;');

    $otherLetter = documentVersionLetter();
    $this->actingAs($viewer)
        ->get(route('back-office.letters.documents.preview', [
            'incomingLetter' => $otherLetter['letter'],
            'letterDocument' => $fixture['initial'],
        ]))
        ->assertNotFound();
});

test('private version access returns conflict for missing files or invalid correction paths', function (): void {
    $fixture = documentVersionLetter();
    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
    );
    $correction = documentVersionCorrection(
        $fixture['letter'],
        $fixture['initial'],
        $head,
        $head->activePositionAssignments()->firstOrFail(),
    );

    Storage::disk('letter-documents')->delete($correction->storage_path);

    $this->actingAs($head)
        ->get(route('back-office.letters.documents.preview', [
            'incomingLetter' => $fixture['letter'],
            'letterDocument' => $correction,
        ]))
        ->assertConflict();

    LetterDocument::withoutEvents(function () use ($correction): void {
        $correction->storage_path = '../outside.pdf';
        $correction->saveQuietly();
    });

    $this->actingAs($head)
        ->get(route('back-office.letters.documents.download', [
            'incomingLetter' => $fixture['letter'],
            'letterDocument' => $correction,
        ]))
        ->assertConflict();
});

test('general affairs head creates a sequential immutable correction version with atomic audit', function (): void {
    $fixture = documentVersionLetter();
    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );
    $originalSnapshot = $fixture['initial']->getAttributes();
    $upload = UploadedFile::fake()->create('surat-koreksi-resmi.pdf', 64, 'application/pdf');

    $this->actingAs($head)
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [
            'document' => $upload,
            'correction_reason' => 'Melengkapi halaman pengesahan dan stempel resmi.',
        ])
        ->assertRedirect(route('back-office.letters.documents.index', $fixture['letter']));

    $created = LetterDocument::query()
        ->where('incoming_letter_id', $fixture['letter']->getKey())
        ->where('version_number', 2)
        ->firstOrFail();

    expect($created->replaces_document_id)->toBe($fixture['initial']->getKey())
        ->and($created->storage_disk)->toBe('letter-documents')
        ->and($created->storage_path)->toStartWith('letter-documents/'.$fixture['letter']->getKey().'/')
        ->and($created->uploaded_by_user_id)->toBe($head->getKey())
        ->and($fixture['initial']->fresh()->getAttributes())->toBe($originalSnapshot)
        ->and(Storage::disk('letter-documents')->exists($created->storage_path))->toBeTrue();

    $audit = AuditLog::query()
        ->where('action', AuditAction::DocumentVersionCreated->value)
        ->where('subject_type', 'letter_document')
        ->where('subject_id', $created->getKey())
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($head->getKey())
        ->and($audit->actor_position_assignment_id)->toBe(
            $head->activePositionAssignments()->firstOrFail()->getKey(),
        )
        ->and($audit->new_values['version_number'])->toBe(2);

    expect(fn () => $created->forceFill(['correction_reason' => 'Diubah'])->save())
        ->toThrow(LogicException::class);
    expect(fn () => $created->delete())->toThrow(LogicException::class);
});

test('version upload rejects invalid input, duplicate fingerprints, and non-registered letter state', function (): void {
    $fixture = documentVersionLetter();
    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );

    $this->actingAs($head)
        ->from(route('back-office.letters.documents.index', $fixture['letter']))
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [
            'document' => UploadedFile::fake()->create('not-a-pdf.txt', 1, 'text/plain'),
            'correction_reason' => 'terlalu pendek',
        ])
        ->assertSessionHasErrors(['document']);

    $duplicate = UploadedFile::fake()->createWithContent(
        'duplicate.pdf',
        '%PDF-1.4 initial official document',
    );

    $this->actingAs($head)
        ->from(route('back-office.letters.documents.index', $fixture['letter']))
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [
            'document' => $duplicate,
            'correction_reason' => 'Dokumen ini sengaja identik dengan versi awal.',
        ])
        ->assertSessionHasErrors(['document']);

    $fixture['letter']->status = IncomingLetterStatus::Routed;
    $fixture['letter']->save();

    $this->actingAs($head)
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [
            'document' => UploadedFile::fake()->create('after-routing.pdf', 10, 'application/pdf'),
            'correction_reason' => 'Koreksi setelah surat sudah diteruskan.',
        ])
        ->assertConflict();

    expect(LetterDocument::query()->where('incoming_letter_id', $fixture['letter']->getKey())->count())
        ->toBe(1);
});

test('create permission never bypasses general affairs head position and upload is rate limited', function (): void {
    $fixture = documentVersionLetter();
    $staff = documentVersionActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );

    $this->actingAs($staff)
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [
            'document' => UploadedFile::fake()->create('staff.pdf', 10, 'application/pdf'),
            'correction_reason' => 'Petugas tidak boleh membuat versi koreksi.',
        ])
        ->assertNotFound();

    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::ViewDocumentVersions,
        PermissionName::CreateDocumentVersions,
    );

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->actingAs($head)
            ->post(route('back-office.letters.documents.store', $fixture['letter']), [])
            ->assertSessionHasErrors('document');
    }

    $this->actingAs($head)
        ->post(route('back-office.letters.documents.store', $fixture['letter']), [])
        ->assertTooManyRequests();
});

test('database and stored file roll back together when audit recording fails', function (): void {
    $fixture = documentVersionLetter();
    $head = documentVersionActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        PermissionName::CreateDocumentVersions,
    );
    $upload = UploadedFile::fake()->create('rollback.pdf', 10, 'application/pdf');

    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit failed.'));
    $this->app->instance(RecordAudit::class, $recordAudit);

    expect(fn () => app(CreateLetterDocumentVersion::class)->execute(
        actor: $head,
        incomingLetter: $fixture['letter'],
        file: $upload,
        correctionReason: 'Memastikan kegagalan audit melakukan rollback penuh.',
    ))->toThrow(RuntimeException::class, 'Audit failed.');

    expect(LetterDocument::query()->where('incoming_letter_id', $fixture['letter']->getKey())->count())
        ->toBe(1)
        ->and(Storage::disk('letter-documents')->allFiles())->toBe([]);
});
