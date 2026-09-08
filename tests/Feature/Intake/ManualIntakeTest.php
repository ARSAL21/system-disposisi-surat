<?php

use App\Actions\CreateManualSubmission;
use App\Actions\CreateOnlineSubmission;
use App\Actions\RecordAudit;
use App\Actions\RegisterIncomingLetter;
use App\Actions\ReturnSubmissionToIntakeStaff;
use App\Actions\ScreenSubmission;
use App\Actions\SubmitLetterSubmission;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\SenderOrganization;
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
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
    Date::setTestNow('2026-09-05 02:00:00');
});

afterEach(function (): void {
    Date::setTestNow();
});

/** @param list<PermissionName> $permissions */
function m8InternalOfficial(string $levelCode, string $unitCode, array $permissions): User
{
    $user = User::factory()->internal()->create();
    $permissionModels = collect($permissions)->map(
        fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
    );
    $role = Role::findOrCreate('m8-'.Str::lower(Str::random(10)), AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);

    $level = PositionLevel::query()->where('code', $levelCode)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = $levelCode;
        $level->name = $levelCode;
        $level->hierarchy_order = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL ? 40 : 10;
        $level->is_active = true;
        $level->save();
    }

    $unit = OrganizationalUnit::query()->where('code', $unitCode)->first();
    if (! $unit instanceof OrganizationalUnit) {
        $unit = new OrganizationalUnit;
        $unit->code = $unitCode;
        $unit->name = $unitCode;
        $unit->is_active = true;
        $unit->save();
    }

    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->organizational_unit_id = $unit->getKey();
    $position->code = 'M8-'.Str::upper(Str::random(10));
    $position->name = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL
        ? 'Kepala Bagian Umum'
        : 'Petugas Surat';
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->ended_at = null;
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $user;
}

/** @return array<string, mixed> */
function m8ManualPayload(array $overrides = []): array
{
    return [
        'sender_organization_name' => 'Forum Warga Wolio',
        'contact_name' => 'Nur Aisyah',
        'contact_email' => '',
        'contact_phone' => '0812-3456-7890',
        'received_at' => '2026-09-04T09:15',
        'external_letter_number' => '017/FWW/IX/2026',
        'external_letter_date' => '2026-09-03',
        'subject' => 'Permohonan audiensi pelayanan lingkungan',
        'summary' => 'Permohonan audiensi mengenai layanan kebersihan lingkungan.',
        'document' => UploadedFile::fake()->createWithContent(
            'surat-forum-warga.pdf',
            "%PDF-1.4\nManual intake test document",
        ),
        'checklist' => [
            ['id' => 'sender', 'checked' => true],
            ['id' => 'letter-metadata', 'checked' => true],
            ['id' => 'document', 'checked' => true],
            ['id' => 'scope', 'checked' => true],
        ],
        'screening_note' => 'Surat fisik dan lampirannya telah diperiksa.',
        ...$overrides,
    ];
}

test('petugas creates a complete manual intake ready for Kabag approval', function (): void {
    $staff = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateManualIntake, PermissionName::ViewIntake],
    );

    $this->actingAs($staff)
        ->get(route('back-office.intake.manual.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('back-office/intake/manual/Create')
            ->where('auth.capabilities.can_create_manual_intake', true)
            ->where('auth.capabilities.can_view_incoming_register', false));

    $response = $this->actingAs($staff)
        ->post(route('back-office.intake.manual.store'), m8ManualPayload());

    $submission = LetterSubmission::query()->sole();
    $document = SubmissionDocument::query()->sole();
    $review = SubmissionReview::query()->sole();

    $response->assertRedirect(route('back-office.intake.submissions.show', $submission));

    expect($submission->source)->toBe(SubmissionSource::Manual)
        ->and($submission->status)->toBe(SubmissionStatus::ReadyForApproval)
        ->and($submission->submitted_by_user_id)->toBeNull()
        ->and($submission->recorded_by_user_id)->toBe($staff->getKey())
        ->and($submission->contact_email)->toBeNull()
        ->and($submission->received_at?->toDateTimeString())->toBe('2026-09-04 01:15:00')
        ->and($review->created_by_user_id)->toBe($staff->getKey())
        ->and($review->outcome->value)->toBe(SubmissionStatus::ReadyForApproval->value)
        ->and($review->checklist)->not->toContain(false)
        ->and($document->storage_disk)->toBe('submission-documents')
        ->and($document->storage_path)->toStartWith($submission->public_id.'/')
        ->and($document->storage_path)->toEndWith('.pdf')
        ->and($document->sha256)->toHaveLength(64);

    Storage::disk('submission-documents')->assertExists($document->storage_path);

    $audit = AuditLog::query()
        ->where('action', AuditAction::ManualSubmissionCreated->value)
        ->sole();

    expect($audit->subject_id)->toBe($submission->getKey())
        ->and($audit->actor_user_id)->toBe($staff->getKey())
        ->and($audit->actor_position_assignment_id)->not->toBeNull();

    $head = m8InternalOfficial(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::DecideIntake],
    );

    $this->actingAs($head)
        ->get(route('back-office.intake.approvals.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('back-office/intake/approvals/Show')
            ->where('submission.source', SubmissionSource::Manual->value)
            ->where('submission.status', SubmissionStatus::ReadyForApproval->value)
            ->where('submission.contact_email', null)
            ->where('submission.received_at', '2026-09-04T01:15:00.000000Z')
            ->has('submission.screening_review'));
});

test('manual intake rolls back records and compensates the private file when audit fails', function (): void {
    $staff = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateManualIntake],
    );
    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit unavailable.'));
    $this->app->instance(RecordAudit::class, $recordAudit);

    expect(fn () => app(CreateManualSubmission::class)->execute(
        actor: $staff,
        attributes: [
            'sender_organization_name' => 'Forum Warga Wolio',
            'contact_name' => 'Nur Aisyah',
            'contact_email' => null,
            'contact_phone' => '0812-3456-7890',
            'received_at' => Date::parse('2026-09-04 01:15:00'),
            'external_letter_number' => '017/FWW/IX/2026',
            'external_letter_date' => Date::parse('2026-09-03'),
            'subject' => 'Permohonan audiensi pelayanan lingkungan',
            'summary' => null,
            'checklist' => m8ManualPayload()['checklist'],
            'screening_note' => 'Surat fisik dan lampirannya telah diperiksa.',
        ],
        file: UploadedFile::fake()->createWithContent(
            'surat-forum-warga.pdf',
            "%PDF-1.4\nManual intake rollback document",
        ),
    ))->toThrow(RuntimeException::class, 'Audit unavailable.');

    expect(LetterSubmission::query()->count())->toBe(0)
        ->and(SubmissionDocument::query()->count())->toBe(0)
        ->and(SubmissionReview::query()->count())->toBe(0)
        ->and(Storage::disk('submission-documents')->allFiles())->toBe([]);
});

test('manual intake requires its permission and an active Bagian Umum staff position', function (): void {
    $withoutPermission = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [],
    );

    $this->actingAs($withoutPermission)
        ->post(route('back-office.intake.manual.store'), m8ManualPayload())
        ->assertForbidden();

    $wrongUnit = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        'BAGIAN_LAIN',
        [PermissionName::CreateManualIntake],
    );

    $this->actingAs($wrongUnit)
        ->get(route('back-office.intake.manual.create'))
        ->assertNotFound();
});

test('manual intake validates official checklist, office receipt time, and PDF contents', function (): void {
    $staff = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateManualIntake],
    );

    $this->actingAs($staff)
        ->post(route('back-office.intake.manual.store'), m8ManualPayload([
            'received_at' => '2026-09-06T10:00',
            'document' => UploadedFile::fake()->createWithContent('not-a-pdf.txt', 'plain text'),
            'checklist' => [
                ['id' => 'sender', 'checked' => true],
                ['id' => 'letter-metadata', 'checked' => true],
                ['id' => 'document', 'checked' => false],
                ['id' => 'scope', 'checked' => true],
            ],
        ]))
        ->assertInvalid(['received_at', 'document', 'checklist']);

    expect(LetterSubmission::query()->count())->toBe(0)
        ->and(Storage::disk('submission-documents')->allFiles())->toBe([]);
});

test('returned manual intake can be corrected and official registration preserves receipt time', function (): void {
    $staff = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateManualIntake, PermissionName::ViewIntake],
    );
    $head = m8InternalOfficial(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::DecideIntake],
    );

    $this->actingAs($staff)
        ->post(route('back-office.intake.manual.store'), m8ManualPayload());

    $submission = LetterSubmission::query()->sole();
    $originalPath = $submission->document()->firstOrFail()->storage_path;

    app(ReturnSubmissionToIntakeStaff::class)->execute(
        $head,
        $submission,
        'Perjelas perihal dan waktu penerimaan surat fisik.',
    );

    $this->actingAs($staff)
        ->get(route('back-office.intake.manual.edit', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('back-office/intake/manual/Create')
            ->where('mode', 'revision')
            ->where('initial.return_note', 'Perjelas perihal dan waktu penerimaan surat fisik.')
            ->where('initial.existing_document.original_filename', 'surat-forum-warga.pdf'));

    $this->actingAs($staff)
        ->post(route('back-office.intake.manual.resubmit', $submission), m8ManualPayload([
            'subject' => 'Permohonan audiensi pengelolaan lingkungan',
            'received_at' => '2026-09-04T08:45',
            'document' => null,
        ]))
        ->assertRedirect(route('back-office.intake.submissions.show', $submission));

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::ReadyForApproval)
        ->and($submission->subject)->toBe('Permohonan audiensi pengelolaan lingkungan')
        ->and($submission->received_at?->toDateTimeString())->toBe('2026-09-04 00:45:00')
        ->and($submission->document()->firstOrFail()->storage_path)->toBe($originalPath)
        ->and($submission->reviews()->count())->toBe(2);

    $sender = new SenderOrganization;
    $sender->name = 'Forum Warga Wolio';
    $sender->is_active = true;
    $sender->save();

    $letter = app(RegisterIncomingLetter::class)->execute($head, $submission, [
        'agenda_number' => '0188/UMUM/IX/2026',
        'note' => 'Administrasi telah disahkan.',
        'sender_organization' => ['mode' => 'existing', 'id' => $sender->getKey()],
    ]);

    expect($letter->received_at->toDateTimeString())->toBe('2026-09-04 00:45:00')
        ->and($letter->agenda_year)->toBe(2026);
});

test('incoming register unifies online and manual letters with database filters and safe payloads', function (): void {
    $staff = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [
            PermissionName::CreateManualIntake,
            PermissionName::ViewIntake,
            PermissionName::ViewIncomingRegister,
            PermissionName::ViewDocumentVersions,
        ],
    );
    $head = m8InternalOfficial(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::DecideIntake, PermissionName::ViewIncomingRegister],
    );
    $sender = new SenderOrganization;
    $sender->name = 'Forum Warga Wolio';
    $sender->is_active = true;
    $sender->save();

    $this->actingAs($staff)
        ->post(route('back-office.intake.manual.store'), m8ManualPayload());
    $manual = LetterSubmission::query()->where('source', SubmissionSource::Manual)->sole();
    app(RegisterIncomingLetter::class)->execute($head, $manual, [
        'agenda_number' => '0188/UMUM/IX/2026',
        'note' => 'Administrasi manual disahkan.',
        'sender_organization' => ['mode' => 'existing', 'id' => $sender->getKey()],
    ]);

    $publicUser = User::factory()->create();
    $online = app(CreateOnlineSubmission::class)->execute($publicUser, [
        'sender_organization_name' => 'Forum Warga Wolio',
        'contact_phone' => null,
        'external_letter_number' => '018/FWW/IX/2026',
        'external_letter_date' => '2026-09-04',
        'subject' => 'Undangan koordinasi lingkungan',
        'summary' => null,
    ]);
    $onlineContents = "%PDF-1.4\nOnline intake test document";
    $onlinePath = $online->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($onlinePath, $onlineContents);

    $onlineDocument = new SubmissionDocument;
    $onlineDocument->letter_submission_id = $online->getKey();
    $onlineDocument->storage_disk = 'submission-documents';
    $onlineDocument->storage_path = $onlinePath;
    $onlineDocument->original_filename = 'undangan-online.pdf';
    $onlineDocument->mime_type = 'application/pdf';
    $onlineDocument->size_bytes = strlen($onlineContents);
    $onlineDocument->sha256 = hash('sha256', $onlineContents);
    $onlineDocument->uploaded_by_user_id = $publicUser->getKey();
    $onlineDocument->save();

    app(SubmitLetterSubmission::class)->execute($publicUser, $online);
    app(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $online,
        outcome: SubmissionReviewOutcome::ReadyForApproval,
        checklist: m8ManualPayload()['checklist'],
        note: 'Pengajuan online lengkap dan sesuai jalur pimpinan.',
    );
    app(RegisterIncomingLetter::class)->execute($head, $online, [
        'agenda_number' => '0189/UMUM/IX/2026',
        'note' => 'Administrasi online disahkan.',
        'sender_organization' => ['mode' => 'existing', 'id' => $sender->getKey()],
    ]);

    $this->actingAs($staff)
        ->get(route('back-office.incoming-letters.index', [
            'source' => SubmissionSource::Manual->value,
            'year' => '2026',
            'search' => 'audiensi',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('back-office/incoming-letters/Index')
            ->where('summary.total_letters', 2)
            ->where('summary.online_letters', 1)
            ->where('summary.manual_letters', 1)
            ->has('letters.data', 1)
            ->where('letters.data.0.source', SubmissionSource::Manual->value)
            ->where('letters.data.0.agenda_number', '0188/UMUM/IX/2026')
            ->where('letters.data.0.links.document_history', fn (mixed $url): bool => is_string($url) && str_contains($url, '/documents'))
            ->missing('letters.data.0.document.storage_disk')
            ->missing('letters.data.0.document.storage_path')
            ->missing('letters.data.0.contact_email')
            ->where('letters.pagination.total', 1));

    $withoutPermission = m8InternalOfficial(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [],
    );
    $this->actingAs($withoutPermission)
        ->get(route('back-office.incoming-letters.index'))
        ->assertForbidden();

    $wrongUnit = m8InternalOfficial(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'BAGIAN_LAIN',
        [PermissionName::ViewIncomingRegister],
    );
    $this->actingAs($wrongUnit)
        ->get(route('back-office.incoming-letters.index'))
        ->assertNotFound();
});
