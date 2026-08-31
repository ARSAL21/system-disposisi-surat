<?php

use App\Actions\CreateOnlineSubmission;
use App\Actions\DeleteSubmissionDraft;
use App\Actions\ReplaceSubmissionDocument;
use App\Actions\SubmitLetterSubmission;
use App\Actions\UpdateSubmissionDraft;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
});

it('verifies audit emission for the full public submission lifecycle', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);

    // 1. Create Draft
    $createAction = app(CreateOnlineSubmission::class);
    $submission = $createAction->execute($owner, [
        'sender_organization_name' => 'Yayasan Warga',
        'contact_name' => 'Budi',
        'contact_email' => 'budi@example.com',
        'contact_phone' => '08123456789',
        'external_letter_number' => 'YW/01/2026',
        'external_letter_date' => '2026-08-01',
        'subject' => 'Permohonan Hibah',
        'summary' => 'Ringkasan hibah.',
    ]);

    $auditCreate = AuditLog::where('action', AuditAction::SubmissionCreated->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditCreate->actor_user_id)->toBe($owner->id)
        ->and($auditCreate->actor_position_assignment_id)->toBeNull()
        ->and($auditCreate->subject_type)->toBe('letter_submission')
        ->and($auditCreate->old_values)->toBeNull()
        ->and($auditCreate->new_values)->toBe([
            'source' => SubmissionSource::Online->value,
            'status' => SubmissionStatus::Draft->value,
        ])
        ->and($auditCreate->metadata)->toBe(['public_id' => $submission->public_id])
        ->and($auditCreate->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');

    // 2. Update Draft
    $updateAction = app(UpdateSubmissionDraft::class);
    $updateAction->execute($owner, $submission, [
        'sender_organization_name' => 'Yayasan Warga Baru',
        'contact_name' => 'Budi Baru',
        'contact_email' => 'budibaru@example.com',
        'contact_phone' => '08123456780',
        'external_letter_number' => 'YW/02/2026',
        'external_letter_date' => '2026-08-02',
        'subject' => 'Permohonan Hibah Diperbarui',
        'summary' => 'Ringkasan diperbarui.',
    ]);

    $auditUpdate = AuditLog::where('action', AuditAction::SubmissionUpdated->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditUpdate->actor_user_id)->toBe($owner->id)
        ->and($auditUpdate->actor_position_assignment_id)->toBeNull()
        ->and($auditUpdate->subject_type)->toBe('letter_submission')
        ->and($auditUpdate->old_values)->toHaveKeys(['sender_organization_name', 'subject', 'summary'])
        ->and($auditUpdate->new_values)->toHaveKeys(['sender_organization_name', 'subject', 'summary'])
        ->and($auditUpdate->new_values['subject'])->toBe('Permohonan Hibah Diperbarui')
        ->and($auditUpdate->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');

    // 3. Attach First Document
    $pdf = UploadedFile::fake()->create('dokumen_awal.pdf', 100, 'application/pdf');
    $replaceDocAction = app(ReplaceSubmissionDocument::class);
    $replaceDocAction->execute($owner, $submission, $pdf);

    $auditAttachDoc = AuditLog::where('action', AuditAction::SubmissionDocumentReplaced->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditAttachDoc->actor_user_id)->toBe($owner->id)
        ->and($auditAttachDoc->actor_position_assignment_id)->toBeNull()
        ->and($auditAttachDoc->subject_type)->toBe('letter_submission')
        ->and($auditAttachDoc->old_values)->toBeNull()
        ->and($auditAttachDoc->new_values)->toHaveKeys(['sha256', 'size_bytes'])
        ->and($auditAttachDoc->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');

    // 4. Replace Existing Document
    $newPdf = UploadedFile::fake()->create('dokumen_revisi.pdf', 150, 'application/pdf');
    $replaceDocAction->execute($owner, $submission, $newPdf);

    $auditReplaceDoc = AuditLog::where('action', AuditAction::SubmissionDocumentReplaced->value)
        ->where('subject_id', $submission->id)
        ->orderByDesc('id')
        ->firstOrFail();

    expect($auditReplaceDoc->actor_user_id)->toBe($owner->id)
        ->and($auditReplaceDoc->actor_position_assignment_id)->toBeNull()
        ->and($auditReplaceDoc->old_values)->toHaveKeys(['sha256', 'size_bytes'])
        ->and($auditReplaceDoc->new_values)->toHaveKeys(['sha256', 'size_bytes'])
        ->and($auditReplaceDoc->old_values['sha256'])->toBe($auditAttachDoc->new_values['sha256']);

    // 5. Submit Letter Submission
    $submitAction = app(SubmitLetterSubmission::class);
    $submitAction->execute($owner, $submission);

    $auditSubmit = AuditLog::where('action', AuditAction::SubmissionSubmitted->value)
        ->where('subject_id', $submission->id)
        ->firstOrFail();

    expect($auditSubmit->actor_user_id)->toBe($owner->id)
        ->and($auditSubmit->actor_position_assignment_id)->toBeNull()
        ->and($auditSubmit->old_values)->toBe(['status' => SubmissionStatus::Draft->value])
        ->and($auditSubmit->new_values)->toHaveKeys(['status', 'submitted_at'])
        ->and($auditSubmit->new_values['status'])->toBe(SubmissionStatus::Submitted->value);

    // 6. Delete Draft
    $anotherDraft = $createAction->execute($owner, [
        'sender_organization_name' => 'Draft Dihapus',
        'contact_name' => 'Budi',
        'contact_email' => 'budi@example.com',
        'subject' => 'Draft Akan Dihapus',
    ]);

    $deleteDraftAction = app(DeleteSubmissionDraft::class);
    $deleteDraftAction->execute($owner, $anotherDraft);

    $auditDelete = AuditLog::where('action', AuditAction::SubmissionDraftDeleted->value)
        ->where('subject_id', $anotherDraft->id)
        ->firstOrFail();

    expect($auditDelete->actor_user_id)->toBe($owner->id)
        ->and($auditDelete->actor_position_assignment_id)->toBeNull()
        ->and($auditDelete->old_values)->toBe(['status' => SubmissionStatus::Draft->value])
        ->and($auditDelete->metadata)->toBe(['public_id' => $anotherDraft->public_id])
        ->and($auditDelete->new_values)->toBeNull();
});
