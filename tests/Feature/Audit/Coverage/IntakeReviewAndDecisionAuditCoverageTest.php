<?php

use App\Actions\CreateOnlineSubmission;
use App\Actions\RegisterIncomingLetter;
use App\Actions\RejectSubmission;
use App\Actions\ReturnSubmissionToIntakeStaff;
use App\Actions\ScreenSubmission;
use App\Actions\SubmitLetterSubmission;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
});

function createReviewPosition(string $levelCode, string $unitCode): Position
{
    $level = PositionLevel::where('code', $levelCode)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = $levelCode;
        $level->name = $levelCode;
        $level->hierarchy_order = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL ? 40 : 10;
        $level->is_active = true;
        $level->save();
    }

    $unit = OrganizationalUnit::where('code', $unitCode)->first();
    if (! $unit instanceof OrganizationalUnit) {
        $unit = new OrganizationalUnit;
        $unit->code = $unitCode;
        $unit->name = 'Unit '.$unitCode;
        $unit->is_active = true;
        $unit->save();
    }

    $position = new Position;
    $position->position_level_id = $level->id;
    $position->organizational_unit_id = $unit->id;
    $position->code = 'POS_'.Str::upper(Str::random(8));
    $position->name = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL ? 'Kepala Bagian Umum' : 'Staf Bagian Umum';
    $position->is_active = true;
    $position->save();

    return $position;
}

function createPersistedAssignment(User $user, Position $position): PositionAssignment
{
    $assignment = new PositionAssignment;
    $assignment->user_id = $user->id;
    $assignment->position_id = $position->id;
    $assignment->started_at = now()->subHour();
    $assignment->ended_at = null;
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $assignment;
}

function createSubmittedLetter(User $owner): LetterSubmission
{
    $submission = app(CreateOnlineSubmission::class)->execute($owner, [
        'sender_organization_name' => 'Yayasan Warga',
        'contact_name' => $owner->name,
        'contact_email' => $owner->email,
        'contact_phone' => '08123456789',
        'external_letter_number' => 'YW/01/2026',
        'external_letter_date' => '2026-08-01',
        'subject' => 'Permohonan Hibah',
        'summary' => 'Ringkasan hibah.',
    ]);

    $contents = '%PDF-1.4 intake verification test';
    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $contents);

    $document = new SubmissionDocument;
    $document->letter_submission_id = $submission->getKey();
    $document->storage_disk = 'submission-documents';
    $document->storage_path = $path;
    $document->original_filename = 'surat-permohonan.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($contents);
    $document->sha256 = hash('sha256', $contents);
    $document->uploaded_by_user_id = $owner->getKey();
    $document->save();

    app(SubmitLetterSubmission::class)->execute($owner, $submission);

    return $submission;
}

it('verifies audit emission for screening review, return, and registration lifecycle', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createSubmittedLetter($owner);

    $staff = User::factory()->internal()->create();
    $staffPos = createReviewPosition(OrganizationCatalog::GENERAL_AFFAIRS_LEVEL, OrganizationCatalog::GENERAL_AFFAIRS_UNIT);
    $staffAssignment = createPersistedAssignment($staff, $staffPos);

    // 1. Revision Requested
    $checklist = [
        ['id' => 'sender', 'checked' => false],
        ['id' => 'letter-metadata', 'checked' => true],
        ['id' => 'document', 'checked' => true],
        ['id' => 'scope', 'checked' => true],
    ];

    app(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $submission,
        outcome: SubmissionReviewOutcome::RevisionRequired,
        checklist: $checklist,
        note: 'Mohon lengkapi identitas.',
    );

    $auditRevision = AuditLog::where('action', AuditAction::SubmissionRevisionRequested->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditRevision->actor_user_id)->toBe($staff->id)
        ->and($auditRevision->actor_position_assignment_id)->toBe($staffAssignment->id)
        ->and($auditRevision->subject_type)->toBe('letter_submission')
        ->and($auditRevision->old_values)->toHaveKey('status')
        ->and($auditRevision->new_values['status'])->toBe(SubmissionStatus::RevisionRequired->value);

    // Resubmit by owner
    app(SubmitLetterSubmission::class)->execute($owner, $submission);
    $auditResubmit = AuditLog::where('action', AuditAction::SubmissionResubmitted->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditResubmit->actor_user_id)->toBe($owner->id)
        ->and($auditResubmit->actor_position_assignment_id)->toBeNull()
        ->and($auditResubmit->new_values['status'])->toBe(SubmissionStatus::Submitted->value);

    // 2. Ready For Approval
    $validChecklist = [
        ['id' => 'sender', 'checked' => true],
        ['id' => 'letter-metadata', 'checked' => true],
        ['id' => 'document', 'checked' => true],
        ['id' => 'scope', 'checked' => true],
    ];

    app(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $submission,
        outcome: SubmissionReviewOutcome::ReadyForApproval,
        checklist: $validChecklist,
        note: 'Dokumen lengkap.',
    );

    $auditReady = AuditLog::where('action', AuditAction::SubmissionReadyForApproval->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditReady->actor_user_id)->toBe($staff->id)
        ->and($auditReady->actor_position_assignment_id)->toBe($staffAssignment->id)
        ->and($auditReady->new_values['status'])->toBe(SubmissionStatus::ReadyForApproval->value);

    // 3. Return to Staff by Head
    $head = User::factory()->internal()->create();
    $headPos = createReviewPosition(OrganizationCatalog::SECTION_HEAD_LEVEL, OrganizationCatalog::GENERAL_AFFAIRS_UNIT);
    $headAssignment = createPersistedAssignment($head, $headPos);

    app(ReturnSubmissionToIntakeStaff::class)->execute(
        actor: $head,
        submission: $submission,
        note: 'Perbaiki catatan disposisi.',
    );

    $auditReturn = AuditLog::where('action', AuditAction::SubmissionReturnedToStaff->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditReturn->actor_user_id)->toBe($head->id)
        ->and($auditReturn->actor_position_assignment_id)->toBe($headAssignment->id)
        ->and($auditReturn->new_values['status'])->toBe(SubmissionStatus::InternalRevisionRequired->value);

    // Rescreen to ready for approval
    app(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $submission,
        outcome: SubmissionReviewOutcome::ReadyForApproval,
        checklist: $validChecklist,
        note: 'Sudah diperbaiki.',
    );

    // 4. Letter Registered & Document Version Created
    $org = new SenderOrganization;
    $org->name = 'Organisasi Terdaftar';
    $org->is_active = true;
    $org->save();

    $incomingLetter = app(RegisterIncomingLetter::class)->execute(
        actor: $head,
        submission: $submission,
        attributes: [
            'agenda_number' => 'AG-AUDIT-001',
            'note' => 'Disahkan.',
            'sender_organization' => [
                'mode' => 'existing',
                'id' => $org->id,
            ],
        ],
    );

    $auditRegistered = AuditLog::where('action', AuditAction::LetterRegistered->value)
        ->where('subject_id', $incomingLetter->id)
        ->firstOrFail();

    expect($auditRegistered->actor_user_id)->toBe($head->id)
        ->and($auditRegistered->actor_position_assignment_id)->toBe($headAssignment->id)
        ->and($auditRegistered->subject_type)->toBe('incoming_letter')
        ->and($auditRegistered->old_values)->toBe(['submission_status' => SubmissionStatus::ReadyForApproval->value])
        ->and($auditRegistered->new_values)->toHaveKeys(['submission_status', 'letter_status', 'agenda_number', 'agenda_year'])
        ->and($auditRegistered->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');

    $auditDoc = AuditLog::where('action', AuditAction::DocumentVersionCreated->value)
        ->firstOrFail();

    expect($auditDoc->actor_user_id)->toBe($head->id)
        ->and($auditDoc->actor_position_assignment_id)->toBe($headAssignment->id)
        ->and($auditDoc->subject_type)->toBe('letter_document')
        ->and($auditDoc->new_values)->toHaveKeys(['incoming_letter_id', 'version_number', 'sha256'])
        ->and($auditDoc->request_id)->toBe($auditRegistered->request_id); // shared operation request ID
});

it('verifies audit emission for submission rejection', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);
    $submission = createSubmittedLetter($owner);

    $staff = User::factory()->internal()->create();
    $staffPos = createReviewPosition(OrganizationCatalog::GENERAL_AFFAIRS_LEVEL, OrganizationCatalog::GENERAL_AFFAIRS_UNIT);
    createPersistedAssignment($staff, $staffPos);

    $validChecklist = [
        ['id' => 'sender', 'checked' => true],
        ['id' => 'letter-metadata', 'checked' => true],
        ['id' => 'document', 'checked' => true],
        ['id' => 'scope', 'checked' => true],
    ];

    app(ScreenSubmission::class)->execute(
        actor: $staff,
        submission: $submission,
        outcome: SubmissionReviewOutcome::ReadyForApproval,
        checklist: $validChecklist,
        note: 'Screening awal.',
    );

    $head = User::factory()->internal()->create();
    $headPos = createReviewPosition(OrganizationCatalog::SECTION_HEAD_LEVEL, OrganizationCatalog::GENERAL_AFFAIRS_UNIT);
    $headAssignment = createPersistedAssignment($head, $headPos);

    app(RejectSubmission::class)->execute(
        actor: $head,
        submission: $submission,
        note: 'Ditolak karena tidak sesuai kewenangan.',
    );

    $auditReject = AuditLog::where('action', AuditAction::SubmissionRejected->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditReject->actor_user_id)->toBe($head->id)
        ->and($auditReject->actor_position_assignment_id)->toBe($headAssignment->id)
        ->and($auditReject->subject_type)->toBe('letter_submission')
        ->and($auditReject->new_values['status'])->toBe(SubmissionStatus::Rejected->value);
});
