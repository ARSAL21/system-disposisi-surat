<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('public');
});

function createTestSubmissionWithFile(User $owner, SubmissionStatus $status = SubmissionStatus::Submitted): LetterSubmission
{
    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = $status;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Yayasan File Test';
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->subject = 'Permohonan Akses Dokumen';
    $submission->submitted_at = $status === SubmissionStatus::Draft ? null : now();
    $submission->save();

    $content = '%PDF-1.4 sample secure document';
    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $content);

    $document = new SubmissionDocument;
    $document->letter_submission_id = $submission->getKey();
    $document->storage_disk = 'submission-documents';
    $document->storage_path = $path;
    $document->original_filename = 'surat-permohonan.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($content);
    $document->sha256 = hash('sha256', $content);
    $document->uploaded_by_user_id = $owner->getKey();
    $document->save();

    return $submission;
}

function createIntakeStaffUser(bool $withActivePosition = true): User
{
    $user = User::factory()->internal()->create();

    $viewPermission = Permission::findOrCreate(PermissionName::ViewIntake->value, AuthorizationCatalog::GUARD_NAME);
    $role = Role::findOrCreate('intake-staff-role', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo($viewPermission);
    $user->assignRole($role);

    if ($withActivePosition) {
        $level = PositionLevel::where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)->first();
        if (! $level instanceof PositionLevel) {
            $level = new PositionLevel;
            $level->code = OrganizationCatalog::GENERAL_AFFAIRS_LEVEL;
            $level->name = 'Staff Level';
            $level->hierarchy_order = 10;
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
        $position->code = 'POS_STAFF_'.Str::upper(Str::random(6));
        $position->name = 'Staf Bagian Umum';
        $position->is_active = true;
        $position->save();

        $assignment = new PositionAssignment;
        $assignment->user_id = $user->id;
        $assignment->position_id = $position->id;
        $assignment->started_at = now()->subHour();
        $assignment->ended_at = null;
        $assignment->save();
    }

    return $user;
}

function createHeadOfGeneralAffairsUser(bool $withActivePosition = true): User
{
    $user = User::factory()->internal()->create();

    $decidePermission = Permission::findOrCreate(PermissionName::DecideIntake->value, AuthorizationCatalog::GUARD_NAME);
    $role = Role::findOrCreate('kabag-role', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo($decidePermission);
    $user->assignRole($role);

    if ($withActivePosition) {
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
        $position->code = 'POS_KABAG_'.Str::upper(Str::random(6));
        $position->name = 'Kepala Bagian Umum';
        $position->is_active = true;
        $position->save();

        $assignment = new PositionAssignment;
        $assignment->user_id = $user->id;
        $assignment->position_id = $position->id;
        $assignment->started_at = now()->subHour();
        $assignment->ended_at = null;
        $assignment->save();
    }

    return $user;
}

it('allows public owner to download submission document and returns standard security headers', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'self'; sandbox")
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');

    expect($response->headers->get('Content-Disposition'))->toMatch('/attachment;\s*filename="?surat-permohonan\.pdf"?/');
});

it('allows public owner to access submission document across various lifecycle statuses', function (SubmissionStatus $status): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, $status);

    $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission))
        ->assertOk();
})->with([
    SubmissionStatus::Draft,
    SubmissionStatus::Submitted,
    SubmissionStatus::RevisionRequired,
    SubmissionStatus::ReadyForApproval,
    SubmissionStatus::InternalRevisionRequired,
    SubmissionStatus::Registered,
    SubmissionStatus::Rejected,
]);

it('denies unrelated public user from downloading another user submission document with 404', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $unrelatedUser = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner);

    $this->actingAs($unrelatedUser)
        ->get(route('public.submissions.document.download', $submission))
        ->assertNotFound();
});

it('allows intake staff with valid position and permission to preview and download document', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);
    $staff = createIntakeStaffUser(withActivePosition: true);

    // Preview (inline)
    $previewResponse = $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.show', $submission));

    $previewResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    expect($previewResponse->headers->get('Content-Disposition'))->toMatch('/inline;\s*filename="?surat-permohonan\.pdf"?/');

    // Download (attachment)
    $downloadResponse = $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.download', $submission));

    $downloadResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($downloadResponse->headers->get('Content-Disposition'))->toMatch('/attachment;\s*filename="?surat-permohonan\.pdf"?/');
});

it('denies intake staff from accessing draft submission documents with 404', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $draftSubmission = createTestSubmissionWithFile($owner, SubmissionStatus::Draft);
    $staff = createIntakeStaffUser(withActivePosition: true);

    $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.show', $draftSubmission))
        ->assertNotFound();

    $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.download', $draftSubmission))
        ->assertNotFound();
});

it('denies intake staff without active position assignment with 404', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);
    $staffWithoutPosition = createIntakeStaffUser(withActivePosition: false);

    $this->actingAs($staffWithoutPosition)
        ->get(route('back-office.intake.submissions.document.show', $submission))
        ->assertNotFound();

    $this->actingAs($staffWithoutPosition)
        ->get(route('back-office.intake.submissions.document.download', $submission))
        ->assertNotFound();
});

it('denies internal user without ViewIntake permission with 403 on intake document routes', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $internalUserWithoutPerm = User::factory()->internal()->create();

    $this->actingAs($internalUserWithoutPerm)
        ->get(route('back-office.intake.submissions.document.show', $submission))
        ->assertForbidden();

    $this->actingAs($internalUserWithoutPerm)
        ->get(route('back-office.intake.submissions.document.download', $submission))
        ->assertForbidden();
});

it('allows Kepala Bagian Umum to preview and download approval documents on visible statuses', function (SubmissionStatus $status): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, $status);
    $kabag = createHeadOfGeneralAffairsUser(withActivePosition: true);

    $previewResponse = $this->actingAs($kabag)
        ->get(route('back-office.intake.approvals.document.show', $submission));

    $previewResponse->assertOk();
    expect($previewResponse->headers->get('Content-Disposition'))->toContain('inline;');

    $downloadResponse = $this->actingAs($kabag)
        ->get(route('back-office.intake.approvals.document.download', $submission));

    $downloadResponse->assertOk();
    expect($downloadResponse->headers->get('Content-Disposition'))->toContain('attachment;');
})->with([
    SubmissionStatus::ReadyForApproval,
    SubmissionStatus::InternalRevisionRequired,
    SubmissionStatus::Registered,
    SubmissionStatus::Rejected,
]);

it('denies Kepala Bagian Umum from accessing approval documents on non-visible statuses with 404', function (SubmissionStatus $status): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, $status);
    $kabag = createHeadOfGeneralAffairsUser(withActivePosition: true);

    $this->actingAs($kabag)
        ->get(route('back-office.intake.approvals.document.show', $submission))
        ->assertNotFound();

    $this->actingAs($kabag)
        ->get(route('back-office.intake.approvals.document.download', $submission))
        ->assertNotFound();
})->with([
    SubmissionStatus::Draft,
    SubmissionStatus::Submitted,
    SubmissionStatus::RevisionRequired,
]);

it('denies Super Admin without business position from accessing private documents with 404', function (): void {
    $superAdmin = User::factory()->internal()->withTwoFactor()->create();
    $superAdminRole = Role::findOrCreate(RoleName::SuperAdmin->value, AuthorizationCatalog::GUARD_NAME);
    foreach (PermissionName::cases() as $perm) {
        Permission::findOrCreate($perm->value, AuthorizationCatalog::GUARD_NAME);
    }
    $superAdminRole->syncPermissions(array_column(PermissionName::cases(), 'value'));
    $superAdmin->assignRole($superAdminRole);

    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::ReadyForApproval);

    // Super-admin without active business position receives 404 (no document bypass)
    $this->actingAs($superAdmin)
        ->get(route('back-office.intake.submissions.document.show', $submission))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('back-office.intake.approvals.document.show', $submission))
        ->assertNotFound();
});

it('sanitizes unsafe original filenames for Content-Disposition attachment and inline', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $document->original_filename = "unsafe\r\n\"filename\"-test-doc\0.pdf";
    $document->save();

    // Test download (attachment)
    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertOk();
    $contentDisposition = $response->headers->get('Content-Disposition');
    expect($contentDisposition)->not->toContain("\r")
        ->and($contentDisposition)->not->toContain("\n")
        ->and($contentDisposition)->not->toContain("\0")
        ->and($contentDisposition)->toMatch('/filename="?unsafefilename-test-doc\.pdf"?/');

    // Test preview (inline) on staff route
    $staff = createIntakeStaffUser(withActivePosition: true);
    $previewResponse = $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.show', $submission));

    $previewResponse->assertOk();
    $previewDisposition = $previewResponse->headers->get('Content-Disposition');
    expect($previewDisposition)->not->toContain("\r")
        ->and($previewDisposition)->not->toContain("\n")
        ->and($previewDisposition)->not->toContain("\0")
        ->and($previewDisposition)->toMatch('/inline;\s*filename="?unsafefilename-test-doc\.pdf"?/');
});

it('ensures very long filenames are truncated cleanly while retaining .pdf extension', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $veryLongName = str_repeat('surat_rahasia_pemerintah_daerah_', 15).'.pdf';
    $document = $submission->document()->firstOrFail();
    $document->original_filename = $veryLongName;
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toMatch('/\.pdf(?:"|;|$)/')
        ->and(strlen($disposition))->toBeLessThan(500);
});

it('handles Unicode filenames with standard UTF-8 and ASCII fallback', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $document->original_filename = 'dokumen_résumé_pengajuan_日本語.pdf';
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toMatch('/filename="?dokumen_resume_pengajuan\.pdf"?/')
        ->and($disposition)->toContain("filename*=utf-8''dokumen_r%C3%A9sum%C3%A9_pengajuan_%E6%97%A5%E6%9C%AC%E8%AA%9E.pdf");
});

it('returns 409 Conflict when document mime_type is invalid in database', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $document->mime_type = 'image/png';
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertStatus(409)
        ->assertSee('Tipe MIME dokumen tidak valid.');
});

it('returns 409 Conflict when physical file is missing from private storage', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    // Delete physical file from disk
    $document = $submission->document()->firstOrFail();
    Storage::disk('submission-documents')->delete($document->storage_path);

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertStatus(409)
        ->assertSee('Dokumen sumber tidak tersedia atau tidak dapat dibaca pada penyimpanan privat.');

    // Ensure storage path is NOT leaked in the response
    expect($response->getContent())->not->toContain($document->storage_path)
        ->and($response->getContent())->not->toContain('submission-documents');
});

it('returns 409 Conflict when storage_path contains directory traversal or invalid prefix without leaking path', function (string $maliciousPath): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $document->storage_path = $maliciousPath;
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertStatus(409)
        ->assertSee('Lokasi dokumen tidak valid.');

    expect($response->getContent())->not->toContain($maliciousPath)
        ->and($response->getContent())->not->toContain('submission-documents');
})->with([
    '../other_dir/secret.pdf',
    'another_public_id/doc.pdf',
    '/etc/passwd.pdf',
    "01MTEST/\0evil.pdf",
    '01MTEST/doc.txt',
]);

it('returns 409 Conflict when storage_disk is not in the private allowed disks without leaking disk name', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $document->storage_disk = 'public';
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertStatus(409)
        ->assertSee('Penyimpanan dokumen tidak valid.');

    expect($response->getContent())->not->toContain('public')
        ->and($response->getContent())->not->toContain($document->storage_path);
});

it('enforces private-document-access rate limiting when request limit is exceeded', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    // Make 60 successful requests within the minute limit
    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($owner)
            ->get(route('public.submissions.document.download', $submission))
            ->assertOk();
    }

    // 61st request exceeds the per-minute limit (60)
    $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission))
        ->assertStatus(429);
});

it('sets Content-Length header strictly matching verified physical file stream size', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createTestSubmissionWithFile($owner, SubmissionStatus::Submitted);

    $document = $submission->document()->firstOrFail();
    $physicalContent = Storage::disk('submission-documents')->get($document->storage_path);
    $exactPhysicalBytes = strlen($physicalContent);

    // Corrupt the database size_bytes column to verify header comes from stream, not DB metadata
    $document->size_bytes = 999999;
    $document->save();

    $response = $this->actingAs($owner)
        ->get(route('public.submissions.document.download', $submission));

    $response->assertOk()
        ->assertHeader('Content-Length', (string) $exactPhysicalBytes);
});
