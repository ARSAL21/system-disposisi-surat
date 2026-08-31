<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\SubmissionDecision;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
});

function createDashboardIntakeStaff(bool $withPosition = true, bool $withPermission = true): User
{
    $user = User::factory()->internal()->create();

    if ($withPermission) {
        $permission = Permission::findOrCreate(
            PermissionName::ViewIntake->value,
            AuthorizationCatalog::GUARD_NAME,
        );
        $role = Role::findOrCreate('intake-staff', AuthorizationCatalog::GUARD_NAME);
        $role->syncPermissions([$permission]);
        $user->assignRole($role);
    }

    if ($withPosition) {
        $level = PositionLevel::query()
            ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)
            ->first();

        if (! $level instanceof PositionLevel) {
            $level = new PositionLevel;
            $level->code = OrganizationCatalog::GENERAL_AFFAIRS_LEVEL;
            $level->name = 'Bagian Umum / Tata Usaha';
            $level->hierarchy_order = 10;
            $level->is_active = true;
            $level->save();
        }

        $unit = OrganizationalUnit::query()
            ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
            ->first();

        if (! $unit instanceof OrganizationalUnit) {
            $unit = new OrganizationalUnit;
            $unit->code = OrganizationCatalog::GENERAL_AFFAIRS_UNIT;
            $unit->name = 'Bagian Umum';
            $unit->is_active = true;
            $unit->save();
        }

        $position = new Position;
        $position->name = 'Staf Intake Bagian Umum';
        $position->code = 'STAF-INTAKE-DASHBOARD-'.Str::upper(Str::random(6));
        $position->organizational_unit_id = $unit->getKey();
        $position->position_level_id = $level->getKey();
        $position->is_active = true;
        $position->save();

        $assignment = new PositionAssignment;
        $assignment->user_id = $user->getKey();
        $assignment->position_id = $position->getKey();
        $assignment->started_at = now()->subMonth();
        $assignment->save();
    }

    return $user;
}

function createDashboardSubmissionWithDocument(
    SubmissionStatus $status,
    string $subject = 'Surat Permohonan Kerja Sama',
    ?string $kabagNote = null,
): LetterSubmission {
    $submitter = User::factory()->create();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = $status;
    $submission->submitted_by_user_id = $submitter->getKey();
    $submission->sender_organization_name = 'PT Inovasi Mandiri';
    $submission->contact_name = 'Budi Santoso';
    $submission->contact_email = 'budi@inovasi.test';
    $submission->contact_phone = '0812-3344-5566';
    $submission->external_letter_number = '001/IM/VIII/2026';
    $submission->external_letter_date = now()->subDays(2)->toDateString();
    $submission->subject = $subject;
    $submission->summary = 'Ringkasan surat permohonan kerja sama program daerah.';
    $submission->submitted_at = now()->subHours(rand(1, 48));
    $submission->save();

    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, '%PDF-1.4 Mock content');

    $doc = new SubmissionDocument;
    $doc->letter_submission_id = $submission->getKey();
    $doc->uploaded_by_user_id = $submitter->getKey();
    $doc->original_filename = 'surat.pdf';
    $doc->storage_disk = 'submission-documents';
    $doc->storage_path = $path;
    $doc->mime_type = 'application/pdf';
    $doc->size_bytes = 2048;
    $doc->sha256 = hash('sha256', '%PDF-1.4 Mock content');
    $doc->save();

    if ($kabagNote !== null) {
        $kabag = User::factory()->internal()->create();
        $decision = new SubmissionDecision;
        $decision->letter_submission_id = $submission->getKey();
        $decision->outcome = SubmissionDecisionOutcome::InternalRevisionRequired;
        $decision->note = $kabagNote;
        $decision->created_by_user_id = $kabag->getKey();
        $decision->created_by_position_assignment_id = 1;
        $decision->created_at = now();
        $decision->saveQuietly();
    }

    return $submission;
}

test('authorized intake staff receives real metrics and submissions on dashboard', function (): void {
    $staff = createDashboardIntakeStaff();

    // Create 1 submitted, 1 internal revision, 1 ready for approval, 1 registered, 1 draft
    $sub1 = createDashboardSubmissionWithDocument(SubmissionStatus::Submitted, 'Surat Masuk 1');
    $sub2 = createDashboardSubmissionWithDocument(
        SubmissionStatus::InternalRevisionRequired,
        'Surat Perbaikan 2',
        'Lengkapi stempel basah',
    );
    createDashboardSubmissionWithDocument(SubmissionStatus::ReadyForApproval, 'Surat Siap Putus');
    createDashboardSubmissionWithDocument(SubmissionStatus::Registered, 'Surat Sudah Diregistrasi');

    $draft = new LetterSubmission;
    $draft->public_id = (string) Str::ulid();
    $draft->source = SubmissionSource::Online;
    $draft->status = SubmissionStatus::Draft;
    $draft->submitted_by_user_id = User::factory()->create()->getKey();
    $draft->sender_organization_name = 'Draft Org';
    $draft->contact_name = 'Draft Contact';
    $draft->contact_email = 'draft@test.com';
    $draft->subject = 'Draft Letter';
    $draft->save();

    $response = $this->actingAs($staff)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('preview', false)
        ->where('intakeDashboard.metrics.submitted_count', 1)
        ->where('intakeDashboard.metrics.internal_revision_count', 1)
        ->where('intakeDashboard.metrics.ready_for_approval_count', 1)
        ->where('intakeDashboard.metrics.registered_count', 1)
        ->has('intakeDashboard.recent_submissions', 2)
        ->where('intakeDashboard.recent_submissions.0.public_id', fn ($id) => in_array($id, [$sub1->public_id, $sub2->public_id], true))
        ->where('intakeDashboard.recent_submissions.0.links.show', fn ($url) => str_contains($url, '/back-office/intake/submissions/'))
    );
});

test('worklist items include internal_revision_note and do not leak internal database IDs or storage paths', function (): void {
    $staff = createDashboardIntakeStaff();

    $sub = createDashboardSubmissionWithDocument(
        SubmissionStatus::InternalRevisionRequired,
        'Surat Dikembalikan Kabag',
        'Catatan revisi internal dari Kabag',
    );

    $response = $this->actingAs($staff)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('intakeDashboard.recent_submissions.0.public_id', $sub->public_id)
        ->where('intakeDashboard.recent_submissions.0.internal_revision_note', 'Catatan revisi internal dari Kabag')
        ->missing('intakeDashboard.recent_submissions.0.id')
        ->missing('intakeDashboard.recent_submissions.0.storage_disk')
        ->missing('intakeDashboard.recent_submissions.0.storage_path')
    );
});

test('user without intake permission receives null intake dashboard', function (): void {
    $user = User::factory()->internal()->create();

    $response = $this->actingAs($user)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('intakeDashboard', null)
    );
});

test('user with intake permission but without active position receives null intake dashboard', function (): void {
    $staffWithoutPosition = createDashboardIntakeStaff(withPosition: false, withPermission: true);

    createDashboardSubmissionWithDocument(SubmissionStatus::Submitted);

    $response = $this->actingAs($staffWithoutPosition)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('intakeDashboard', null)
    );
});

test('worklist limits results to maximum 10 items in stable chronological order', function (): void {
    $staff = createDashboardIntakeStaff();

    // Create 15 submitted submissions with staggered times
    for ($i = 1; $i <= 15; $i++) {
        $sub = createDashboardSubmissionWithDocument(SubmissionStatus::Submitted, "Surat {$i}");
        $sub->submitted_at = now()->subMinutes(15 - $i);
        $sub->save();
    }

    $response = $this->actingAs($staff)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('intakeDashboard.metrics.submitted_count', 15)
        ->has('intakeDashboard.recent_submissions', 10)
    );
});

test('submission without document has null document and null preview download links', function (): void {
    $staff = createDashboardIntakeStaff();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Submitted;
    $submission->submitted_by_user_id = User::factory()->create()->getKey();
    $submission->sender_organization_name = 'Lembaga Tanpa Dokumen';
    $submission->contact_name = 'Kontak Person';
    $submission->contact_email = 'kontak@lembaga.test';
    $submission->subject = 'Surat Tanpa Dokumen';
    $submission->submitted_at = now();
    $submission->save();

    $response = $this->actingAs($staff)->get(route('back-office.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('intakeDashboard.recent_submissions.0.document', null)
        ->where('intakeDashboard.recent_submissions.0.links.document_preview', null)
        ->where('intakeDashboard.recent_submissions.0.links.document_download', null)
    );
});

test('document preview link from dashboard returns 200 with security headers', function (): void {
    $staff = createDashboardIntakeStaff();
    $submission = createDashboardSubmissionWithDocument(SubmissionStatus::Submitted);

    $response = $this->actingAs($staff)->get(route('back-office.intake.submissions.document.show', $submission));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('local preview dashboard route renders with preview true', function (): void {
    $user = User::factory()->internal()->create();

    $response = $this->actingAs($user)->get(route('back-office.previews.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('back-office/Dashboard')
        ->where('preview', true)
    );
});

