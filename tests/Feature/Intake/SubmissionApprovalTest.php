<?php

use App\Actions\RecordAudit;
use App\Actions\RegisterIncomingLetter;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionReviewOutcome;
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
use App\Models\SubmissionDecision;
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

function approvalLevel(string $code): PositionLevel
{
    $level = PositionLevel::query()->where('code', $code)->first();

    if ($level instanceof PositionLevel) {
        return $level;
    }

    $level = new PositionLevel;
    $level->code = $code;
    $level->name = $code === OrganizationCatalog::SECTION_HEAD_LEVEL
        ? 'Kepala Bagian'
        : 'Bagian Umum / Tata Usaha';
    $level->hierarchy_order = $code === OrganizationCatalog::SECTION_HEAD_LEVEL ? 40 : 10;
    $level->is_active = true;
    $level->save();

    return $level;
}

function approvalUnit(string $code = OrganizationCatalog::GENERAL_AFFAIRS_UNIT): OrganizationalUnit
{
    $unit = OrganizationalUnit::query()->where('code', $code)->first();

    if ($unit instanceof OrganizationalUnit) {
        return $unit;
    }

    $unit = new OrganizationalUnit;
    $unit->code = $code;
    $unit->name = str_replace('_', ' ', $code);
    $unit->is_active = true;
    $unit->save();

    return $unit;
}

function approvalAssignment(User $user, string $levelCode, string $unitCode): PositionAssignment
{
    $position = new Position;
    $position->position_level_id = approvalLevel($levelCode)->getKey();
    $position->organizational_unit_id = approvalUnit($unitCode)->getKey();
    $position->code = 'POSITION-'.Str::upper(Str::random(10));
    $position->name = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL
        ? 'Kepala Bagian Umum'
        : 'Administrasi Surat';
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subMinute();
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $assignment;
}

function approvalGrant(User $user, string $roleName, PermissionName ...$permissions): void
{
    $permissionModels = collect($permissions)->map(
        fn (PermissionName $permission) => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
    );
    $role = Role::findOrCreate($roleName, AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);
}

function approvalHead(
    bool $withPermission = true,
    string $unitCode = OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
): User {
    $user = User::factory()->internal()->create();

    if ($withPermission) {
        approvalGrant($user, 'approval-head-'.Str::lower(Str::random(6)), PermissionName::DecideIntake);
    }

    approvalAssignment($user, OrganizationCatalog::SECTION_HEAD_LEVEL, $unitCode);

    return $user;
}

function approvalStaff(): User
{
    $user = User::factory()->internal()->create();
    approvalGrant(
        $user,
        'approval-staff-'.Str::lower(Str::random(6)),
        PermissionName::ViewIntake,
        PermissionName::ScreenIntake,
    );
    approvalAssignment(
        $user,
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );

    return $user;
}

function approvalReadySubmission(string $contents = '%PDF-1.4 approval test'): LetterSubmission
{
    $owner = User::factory()->create();
    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::ReadyForApproval;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Forum Warga Kota';
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->contact_phone = '081234567890';
    $submission->external_letter_number = 'FWK/001/2026';
    $submission->external_letter_date = '2026-08-20';
    $submission->subject = 'Permohonan audiensi warga';
    $submission->summary = 'Permohonan audiensi resmi.';
    $submission->submitted_at = now()->subHour();
    $submission->save();

    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $contents);

    $document = new SubmissionDocument;
    $document->letter_submission_id = $submission->getKey();
    $document->storage_disk = 'submission-documents';
    $document->storage_path = $path;
    $document->original_filename = 'surat-audiensi.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($contents);
    $document->sha256 = hash('sha256', $contents);
    $document->uploaded_by_user_id = $owner->getKey();
    $document->save();

    $staff = approvalStaff();
    $review = new SubmissionReview;
    $review->letter_submission_id = $submission->getKey();
    $review->outcome = SubmissionReviewOutcome::ReadyForApproval;
    $review->checklist = [
        'sender' => true,
        'letter-metadata' => true,
        'document' => true,
        'scope' => true,
    ];
    $review->note = 'Seluruh kelengkapan telah diperiksa.';
    $review->created_by_user_id = $staff->getKey();
    $review->created_by_position_assignment_id = $staff->activePositionAssignments()->firstOrFail()->getKey();
    $review->save();

    return $submission;
}

/** @return array<string, mixed> */
function registerDecisionPayload(string $agendaNumber, ?int $organizationId = null): array
{
    return [
        'outcome' => SubmissionDecisionOutcome::Registered->value,
        'agenda_number' => $agendaNumber,
        'note' => 'Diregistrasikan untuk diteruskan melalui jalur pimpinan.',
        'sender_organization' => $organizationId === null
            ? [
                'mode' => 'new',
                'name' => 'Forum Warga Kota',
                'address' => 'Jalan Utama 10',
                'contact' => '081234567890',
            ]
            : ['mode' => 'existing', 'id' => $organizationId],
    ];
}

/** @return list<array{id: string, checked: bool}> */
function approvalChecklist(bool $complete = true): array
{
    return [
        ['id' => 'sender', 'checked' => true],
        ['id' => 'letter-metadata', 'checked' => $complete],
        ['id' => 'document', 'checked' => $complete],
        ['id' => 'scope', 'checked' => $complete],
    ];
}

it('enforces permission and the active Kepala Bagian Umum position boundary', function (): void {
    $ready = approvalReadySubmission();
    $head = approvalHead();

    $this->actingAs($head)
        ->get(route('back-office.intake.approvals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/intake/approvals/Index')
            ->where('auth.capabilities.can_decide_intake', true)
            ->has('submissions.data', 1)
            ->where('submissions.data.0.public_id', $ready->public_id)
            ->missing('submissions.data.0.id')
            ->missing('submissions.data.0.document.storage_path'));

    $this->actingAs(approvalHead(withPermission: false))
        ->get(route('back-office.intake.approvals.index'))
        ->assertForbidden();

    $wrongUnitHead = approvalHead(unitCode: 'BAGIAN_HUKUM');
    $this->actingAs($wrongUnitHead)
        ->get(route('back-office.intake.approvals.index'))
        ->assertForbidden();
    $this->actingAs($wrongUnitHead)
        ->get(route('back-office.intake.approvals.show', $ready))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('back-office.intake.approvals.show', $ready))
        ->assertNotFound();
});

it('returns a submission to staff and permits only an internal resubmission', function (): void {
    $submission = approvalReadySubmission();
    $head = approvalHead();
    $headAssignment = $head->activePositionAssignments()->firstOrFail();

    $this->actingAs($head)
        ->postJson(route('back-office.intake.approvals.decisions.store', $submission), [
            'outcome' => SubmissionDecisionOutcome::InternalRevisionRequired->value,
            'note' => 'Perjelas catatan pengantar sebelum surat diregistrasikan.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', SubmissionStatus::InternalRevisionRequired->value);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::InternalRevisionRequired)
        ->and(SubmissionDecision::query()->count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditAction::SubmissionReturnedToStaff->value,
        'actor_position_assignment_id' => $headAssignment->getKey(),
        'subject_id' => $submission->getKey(),
    ]);

    $staff = approvalStaff();
    $route = route('back-office.intake.submissions.screen', $submission);
    $this->actingAs($staff)->postJson($route, [
        'outcome' => SubmissionReviewOutcome::RevisionRequired->value,
        'checklist' => approvalChecklist(false),
        'note' => 'Tidak boleh meminta koreksi publik dari tahap ini.',
    ])->assertUnprocessable()->assertJsonValidationErrors('outcome');

    $this->actingAs($staff)->postJson($route, [
        'outcome' => SubmissionReviewOutcome::ReadyForApproval->value,
        'checklist' => approvalChecklist(),
        'note' => 'Catatan pengantar telah diperjelas.',
    ])->assertOk()->assertJsonPath('data.status', SubmissionStatus::ReadyForApproval->value);
});

it('rejects a submission finally and exposes only the formal reason to its public owner', function (): void {
    $submission = approvalReadySubmission();
    $owner = $submission->submitter()->firstOrFail();
    $head = approvalHead();
    $reason = 'Pengajuan tidak termasuk kewenangan administratif kantor ini.';

    $this->actingAs($head)
        ->postJson(route('back-office.intake.approvals.decisions.store', $submission), [
            'outcome' => SubmissionDecisionOutcome::Rejected->value,
            'note' => $reason,
        ])->assertOk()->assertJsonPath('data.status', SubmissionStatus::Rejected->value);

    $this->actingAs($owner)
        ->getJson(route('public.submissions.show', $submission))
        ->assertOk()
        ->assertJsonPath('data.rejection_note', $reason)
        ->assertJsonMissingPath('data.latest_decision');

    $this->actingAs($head)
        ->postJson(route('back-office.intake.approvals.decisions.store', $submission), [
            'outcome' => SubmissionDecisionOutcome::Rejected->value,
            'note' => $reason,
        ])->assertConflict();

    expect(IncomingLetter::query()->count())->toBe(0);
});

it('registers an incoming letter, immutable document version, decision, and audits atomically', function (): void {
    $submission = approvalReadySubmission();
    $head = approvalHead();
    $assignment = $head->activePositionAssignments()->firstOrFail();
    $organization = new SenderOrganization;
    $organization->name = 'Forum Warga Kota';
    $organization->is_active = true;
    $organization->save();

    $this->actingAs($head)
        ->postJson(
            route('back-office.intake.approvals.decisions.store', $submission),
            registerDecisionPayload('AG-0001', $organization->getKey()),
        )
        ->assertOk()
        ->assertJsonPath('data.status', SubmissionStatus::Registered->value)
        ->assertJsonPath('data.registration.agenda_number', 'AG-0001');

    $incomingLetter = IncomingLetter::query()->firstOrFail();
    $sourceDocument = $submission->document()->firstOrFail();
    $letterDocument = LetterDocument::query()->firstOrFail();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Registered)
        ->and($incomingLetter->letter_submission_id)->toBe($submission->getKey())
        ->and($incomingLetter->registered_by_position_assignment_id)->toBe($assignment->getKey())
        ->and($letterDocument->version_number)->toBe(1)
        ->and($letterDocument->source_submission_document_id)->toBe($sourceDocument->getKey())
        ->and($letterDocument->sha256)->toBe($sourceDocument->sha256)
        ->and(SubmissionDecision::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::LetterRegistered->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::DocumentVersionCreated->value)->count())->toBe(1);

    expect(fn () => $letterDocument->delete())->toThrow(LogicException::class)
        ->and(fn () => SubmissionDecision::query()->firstOrFail()->delete())->toThrow(LogicException::class);

    $duplicateAgendaSubmission = approvalReadySubmission();
    $this->actingAs($head)
        ->postJson(
            route('back-office.intake.approvals.decisions.store', $duplicateAgendaSubmission),
            registerDecisionPayload('AG-0001', $organization->getKey()),
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('agenda_number');

    expect($duplicateAgendaSubmission->refresh()->status)->toBe(SubmissionStatus::ReadyForApproval);
});

it('blocks tampered documents and rolls back registration when audit persistence fails', function (): void {
    $tamperedSubmission = approvalReadySubmission();
    $head = approvalHead();
    $document = $tamperedSubmission->document()->firstOrFail();
    Storage::disk($document->storage_disk)->put($document->storage_path, '%PDF-1.4 tampered');

    $this->actingAs($head)
        ->postJson(
            route('back-office.intake.approvals.decisions.store', $tamperedSubmission),
            registerDecisionPayload('AG-0002'),
        )
        ->assertConflict();

    expect($tamperedSubmission->refresh()->status)->toBe(SubmissionStatus::ReadyForApproval)
        ->and(IncomingLetter::query()->count())->toBe(0)
        ->and(LetterDocument::query()->count())->toBe(0)
        ->and(SubmissionDecision::query()->count())->toBe(0);

    $submission = approvalReadySubmission();
    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit unavailable.'));
    $this->app->instance(RecordAudit::class, $recordAudit);

    expect(fn () => $this->app->make(RegisterIncomingLetter::class)->execute(
        actor: $head,
        submission: $submission,
        attributes: [
            'agenda_number' => 'AG-0003',
            'note' => null,
            'sender_organization' => [
                'mode' => 'new',
                'name' => 'Instansi Pengujian',
                'address' => null,
                'contact' => null,
            ],
        ],
    ))->toThrow(RuntimeException::class, 'Audit unavailable.');

    expect($submission->refresh()->status)->toBe(SubmissionStatus::ReadyForApproval)
        ->and(IncomingLetter::query()->count())->toBe(0)
        ->and(LetterDocument::query()->count())->toBe(0)
        ->and(SubmissionDecision::query()->count())->toBe(0);
});

it('protects approval document access with the same private resource boundary', function (): void {
    $submission = approvalReadySubmission();
    $head = approvalHead();

    $this->actingAs($head)
        ->get(route('back-office.intake.approvals.document.show', $submission))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    $this->actingAs(approvalHead(unitCode: 'BAGIAN_KEUANGAN'))
        ->get(route('back-office.intake.approvals.document.show', $submission))
        ->assertNotFound();
});
