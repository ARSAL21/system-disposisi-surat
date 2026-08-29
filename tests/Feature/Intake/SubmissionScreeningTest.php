<?php

use App\Actions\RecordAudit;
use App\Actions\ScreenSubmission;
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
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
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

function m3IntakeStaff(bool $withPosition = true, bool $withPermission = true): User
{
    $user = User::factory()->internal()->create();

    if ($withPermission) {
        $permissions = collect([
            PermissionName::ViewIntake,
            PermissionName::ScreenIntake,
        ])->map(fn (PermissionName $permission) => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ));
        $role = Role::findOrCreate('intake-staff', AuthorizationCatalog::GUARD_NAME);
        $role->syncPermissions($permissions);
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

        $unit = new OrganizationalUnit;
        $unit->code = 'BAGIAN-UMUM-'.Str::lower(Str::random(6));
        $unit->name = 'Bagian Umum';
        $unit->is_active = true;
        $unit->save();

        $position = new Position;
        $position->position_level_id = $level->getKey();
        $position->organizational_unit_id = $unit->getKey();
        $position->code = 'ADMIN-SURAT-'.Str::lower(Str::random(6));
        $position->name = 'Administrasi Surat';
        $position->is_active = true;
        $position->save();

        $assignment = new PositionAssignment;
        $assignment->user_id = $user->getKey();
        $assignment->position_id = $position->getKey();
        $assignment->started_at = now()->subMinute();
        $assignment->assigned_by_user_id = null;
        $assignment->save();
    }

    return $user;
}

function m3SubmittedLetter(): LetterSubmission
{
    $owner = User::factory()->create();
    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Submitted;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->recorded_by_user_id = null;
    $submission->sender_organization_name = 'Universitas Contoh';
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->contact_phone = '081234567890';
    $submission->external_letter_number = 'EXT/001/2026';
    $submission->external_letter_date = '2026-08-20';
    $submission->subject = 'Permohonan audiensi';
    $submission->summary = 'Permohonan audiensi resmi.';
    $submission->submitted_at = now();
    $submission->save();

    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, '%PDF-1.4 secure test');

    $document = new SubmissionDocument;
    $document->letter_submission_id = $submission->getKey();
    $document->storage_disk = 'submission-documents';
    $document->storage_path = $path;
    $document->original_filename = 'surat.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = Storage::disk('submission-documents')->size($path);
    $document->sha256 = hash('sha256', '%PDF-1.4 secure test');
    $document->uploaded_by_user_id = $owner->getKey();
    $document->save();

    return $submission;
}

/** @return list<array{id: string, checked: bool}> */
function m3ScreeningChecklist(bool $complete = true): array
{
    return [
        ['id' => 'sender', 'checked' => true],
        ['id' => 'letter-metadata', 'checked' => $complete],
        ['id' => 'document', 'checked' => $complete],
        ['id' => 'scope', 'checked' => $complete],
    ];
}

it('enforces permission, account, and active general affairs position boundaries', function (): void {
    $submission = m3SubmittedLetter();
    $staff = m3IntakeStaff();

    $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/intake/submissions/Index')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.public_id', $submission->public_id)
            ->missing('submissions.data.0.id')
            ->missing('submissions.data.0.document.storage_path'));

    $this->actingAs(m3IntakeStaff(withPermission: false))
        ->get(route('back-office.intake.submissions.index'))
        ->assertForbidden();

    $this->actingAs(m3IntakeStaff(withPosition: false))
        ->get(route('back-office.intake.submissions.show', $submission))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('back-office.intake.submissions.index'))
        ->assertNotFound();
});

it('records a revision request atomically and allows the owner to correct and resubmit', function (): void {
    $submission = m3SubmittedLetter();
    $owner = $submission->submitter()->firstOrFail();
    $staff = m3IntakeStaff();
    $assignment = $staff->activePositionAssignments()->firstOrFail();

    $this->actingAs($staff)
        ->postJson(route('back-office.intake.submissions.screen', $submission), [
            'outcome' => SubmissionReviewOutcome::RevisionRequired->value,
            'checklist' => m3ScreeningChecklist(false),
            'note' => 'Nomor surat dan halaman pengesahan harus dilengkapi.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', SubmissionStatus::RevisionRequired->value);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::RevisionRequired)
        ->and(SubmissionReview::query()->count())->toBe(1);

    $review = SubmissionReview::query()->firstOrFail();
    expect($review->created_by_position_assignment_id)->toBe($assignment->id)
        ->and($review->checklist['letter-metadata'])->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'actor_user_id' => $staff->id,
        'actor_position_assignment_id' => $assignment->id,
        'action' => AuditAction::SubmissionRevisionRequested->value,
        'subject_id' => $submission->id,
    ]);

    $this->actingAs($owner)
        ->patchJson(route('public.submissions.update', $submission), [
            'sender_organization_name' => $submission->sender_organization_name,
            'contact_phone' => $submission->contact_phone,
            'external_letter_number' => 'EXT/001-REV/2026',
            'external_letter_date' => '2026-08-20',
            'subject' => $submission->subject,
            'summary' => $submission->summary,
        ])
        ->assertOk();

    $this->actingAs($owner)
        ->postJson(route('public.submissions.submit', $submission))
        ->assertOk()
        ->assertJsonPath('data.status', SubmissionStatus::Submitted->value);

    $this->assertDatabaseHas('audit_logs', [
        'actor_user_id' => $owner->id,
        'action' => AuditAction::SubmissionResubmitted->value,
        'subject_id' => $submission->id,
    ]);
});

it('requires a complete official checklist and rejects duplicate screening transitions', function (): void {
    $submission = m3SubmittedLetter();
    $staff = m3IntakeStaff();
    $route = route('back-office.intake.submissions.screen', $submission);

    $this->actingAs($staff)->postJson($route, [
        'outcome' => SubmissionReviewOutcome::ReadyForApproval->value,
        'checklist' => m3ScreeningChecklist(false),
        'note' => null,
    ])->assertUnprocessable()->assertJsonValidationErrors('checklist');

    expect(SubmissionReview::query()->count())->toBe(0)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Submitted);

    $this->actingAs($staff)->postJson($route, [
        'outcome' => SubmissionReviewOutcome::ReadyForApproval->value,
        'checklist' => m3ScreeningChecklist(),
        'note' => 'Seluruh kelengkapan telah diperiksa.',
    ])->assertOk();

    $this->actingAs($staff)->postJson($route, [
        'outcome' => SubmissionReviewOutcome::ReadyForApproval->value,
        'checklist' => m3ScreeningChecklist(),
        'note' => null,
    ])->assertConflict();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::ReadyForApproval)
        ->and(SubmissionReview::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::SubmissionReadyForApproval->value)->count())->toBe(1);
});

it('protects private PDF access and rolls back screening when audit persistence fails', function (): void {
    $submission = m3SubmittedLetter();
    $staff = m3IntakeStaff();

    $this->actingAs($staff)
        ->get(route('back-office.intake.submissions.document.show', $submission))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    $this->actingAs(m3IntakeStaff(withPosition: false))
        ->get(route('back-office.intake.submissions.document.show', $submission))
        ->assertNotFound();

    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit unavailable.'));
    $this->app->instance(RecordAudit::class, $recordAudit);
    $this->withoutExceptionHandling();

    expect(fn () => $this->app->make(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $submission,
        outcome: SubmissionReviewOutcome::ReadyForApproval,
        checklist: m3ScreeningChecklist(),
        note: null,
    ))->toThrow(RuntimeException::class, 'Audit unavailable.');

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Submitted)
        ->and(SubmissionReview::query()->count())->toBe(0);
});
