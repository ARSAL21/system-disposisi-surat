<?php

use App\Actions\RecordAudit;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
});

function publicSubmissionUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Public Sender',
        'email' => Str::lower(Str::random(12)).'@gmail.com',
        'email_verified_at' => now(),
        'account_type' => AccountType::PublicAccount,
        'is_active' => true,
    ], $overrides));
}

function publicSubmissionPayload(array $overrides = []): array
{
    return array_merge([
        'sender_organization_name' => 'Universitas Contoh',
        'contact_phone' => '081234567890',
        'external_letter_number' => 'EXT/001/2026',
        'external_letter_date' => '2026-08-20',
        'subject' => 'Permohonan audiensi',
        'summary' => 'Permohonan audiensi resmi dari organisasi pengirim.',
    ], $overrides);
}

function createPublicDraft(User $user, array $overrides = []): LetterSubmission
{
    test()->actingAs($user)
        ->postJson(route('public.submissions.store'), publicSubmissionPayload($overrides))
        ->assertCreated();

    return LetterSubmission::query()
        ->where('submitted_by_user_id', $user->getKey())
        ->latest('id')
        ->firstOrFail();
}

function fakeSubmissionPdf(string $name = 'surat.pdf', int $kilobytes = 10, string $mime = 'application/pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, $mime);
}

it('protects the public submission area with every M0 account boundary', function (): void {
    $payload = publicSubmissionPayload();

    $this->postJson(route('public.submissions.store'), $payload)
        ->assertUnauthorized();

    $unverified = publicSubmissionUser(['email_verified_at' => null]);
    $this->actingAs($unverified)
        ->postJson(route('public.submissions.store'), $payload)
        ->assertForbidden();

    $inactive = publicSubmissionUser(['is_active' => false]);
    $this->actingAs($inactive)
        ->postJson(route('public.submissions.store'), $payload)
        ->assertForbidden();

    $internal = publicSubmissionUser(['account_type' => AccountType::InternalAccount]);
    $this->actingAs($internal)
        ->postJson(route('public.submissions.store'), $payload)
        ->assertNotFound();
});

it('creates an online draft with a ULID and server owned identity fields', function (): void {
    $user = publicSubmissionUser([
        'name' => 'Pemilik Akun',
        'email' => 'pemilik@gmail.com',
    ]);

    $response = $this->actingAs($user)->postJson(route('public.submissions.store'), [
        ...publicSubmissionPayload(),
        'public_id' => '01AAAAAAAAAAAAAAAAAAAAAAAA',
        'source' => SubmissionSource::Manual->value,
        'status' => SubmissionStatus::Registered->value,
        'submitted_by_user_id' => 999,
        'contact_name' => 'Nama Palsu',
        'contact_email' => 'palsu@gmail.com',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.source', SubmissionSource::Online->value)
        ->assertJsonPath('data.status', SubmissionStatus::Draft->value)
        ->assertJsonPath('data.contact_name', 'Pemilik Akun')
        ->assertJsonPath('data.contact_email', 'pemilik@gmail.com')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.storage_path')
        ->assertJsonMissingPath('data.sha256');

    $submission = LetterSubmission::query()->firstOrFail();

    expect(Str::isUlid($submission->public_id))->toBeTrue()
        ->and($submission->public_id)->not->toBe('01AAAAAAAAAAAAAAAAAAAAAAAA')
        ->and($submission->submitted_by_user_id)->toBe($user->id)
        ->and($submission->recorded_by_user_id)->toBeNull()
        ->and($submission->submitted_at)->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'actor_user_id' => $user->id,
        'action' => AuditAction::SubmissionCreated->value,
        'subject_type' => 'letter_submission',
        'subject_id' => $submission->id,
    ]);
});

it('validates organization metadata before creating a draft', function (): void {
    $user = publicSubmissionUser();

    $this->actingAs($user)
        ->postJson(route('public.submissions.store'), [
            'sender_organization_name' => '',
            'external_letter_date' => '2099-01-01',
            'subject' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_organization_name',
            'external_letter_date',
            'subject',
        ]);

    expect(LetterSubmission::query()->count())->toBe(0)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('scopes collections at the database query and hides every cross owner operation', function (): void {
    $owner = publicSubmissionUser();
    $otherUser = publicSubmissionUser();
    $ownedSubmission = createPublicDraft($owner, ['subject' => 'Milik owner']);
    $otherSubmission = createPublicDraft($otherUser, ['subject' => 'Milik user lain']);

    $this->actingAs($owner)
        ->getJson(route('public.submissions.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $ownedSubmission->public_id)
        ->assertJsonMissing(['public_id' => $otherSubmission->public_id]);

    $this->actingAs($owner)
        ->getJson(route('public.submissions.show', $otherSubmission))
        ->assertNotFound();

    $this->actingAs($owner)
        ->patchJson(route('public.submissions.update', $otherSubmission), publicSubmissionPayload())
        ->assertNotFound();

    $this->actingAs($owner)
        ->patchJson(route('public.submissions.update', $otherSubmission), [])
        ->assertNotFound();

    $this->actingAs($owner)
        ->putJson(route('public.submissions.document.replace', $otherSubmission), [
            'document' => fakeSubmissionPdf(),
        ])
        ->assertNotFound();

    $this->actingAs($owner)
        ->putJson(route('public.submissions.document.replace', $otherSubmission), [])
        ->assertNotFound();

    $this->actingAs($owner)
        ->getJson(route('public.submissions.document.download', $otherSubmission))
        ->assertNotFound();

    $this->actingAs($owner)
        ->postJson(route('public.submissions.submit', $otherSubmission))
        ->assertNotFound();

    $this->actingAs($owner)
        ->deleteJson(route('public.submissions.destroy', $otherSubmission))
        ->assertNotFound();
});

it('updates only draft metadata and records the changed values', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)
        ->patchJson(route('public.submissions.update', $submission), publicSubmissionPayload([
            'subject' => 'Subjek yang diperbarui',
            'summary' => null,
        ]))
        ->assertOk()
        ->assertJsonPath('data.subject', 'Subjek yang diperbarui')
        ->assertJsonPath('data.summary', null);

    $audit = AuditLog::query()
        ->where('action', AuditAction::SubmissionUpdated->value)
        ->firstOrFail();

    expect($audit->old_values)->toHaveKey('subject', 'Permohonan audiensi')
        ->and($audit->new_values)->toHaveKey('subject', 'Subjek yang diperbarui')
        ->and($audit->metadata['changed_fields'])->toContain('subject', 'summary');
});

it('accepts one private PDF with a random path, fingerprint, and authorized download', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);
    $file = fakeSubmissionPdf('Nomor Surat Resmi.pdf');
    $expectedHash = hash_file('sha256', $file->getRealPath());

    $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => $file,
        ])
        ->assertOk()
        ->assertJsonPath('data.document.original_filename', 'Nomor Surat Resmi.pdf')
        ->assertJsonMissingPath('data.document.storage_path')
        ->assertJsonMissingPath('data.document.sha256');

    $document = SubmissionDocument::query()->firstOrFail();

    expect($document->storage_disk)->toBe('submission-documents')
        ->and($document->storage_path)->toStartWith($submission->public_id.'/')
        ->and($document->storage_path)->not->toContain('Nomor Surat Resmi')
        ->and($document->sha256)->toBe($expectedHash);

    Storage::disk('submission-documents')->assertExists($document->storage_path);

    $this->actingAs($user)
        ->get(route('public.submissions.document.download', $submission))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload('Nomor Surat Resmi.pdf');
});

it('rejects disguised or oversized document uploads', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => fakeSubmissionPdf('surat.txt'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document');

    $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => fakeSubmissionPdf('surat.pdf', 10, 'text/plain'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document');

    $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => fakeSubmissionPdf('surat.pdf', 20 * 1024 + 1),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document');

    expect(SubmissionDocument::query()->count())->toBe(0);
    Storage::disk('submission-documents')->assertDirectoryEmpty('/');
});

it('replaces a draft document without leaving the previous file or a second row', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)->putJson(route('public.submissions.document.replace', $submission), [
        'document' => fakeSubmissionPdf('pertama.pdf'),
    ])->assertOk();

    $oldPath = SubmissionDocument::query()->firstOrFail()->storage_path;

    $this->actingAs($user)->putJson(route('public.submissions.document.replace', $submission), [
        'document' => fakeSubmissionPdf('kedua.pdf'),
    ])->assertOk();

    $document = SubmissionDocument::query()->firstOrFail();

    expect(SubmissionDocument::query()->count())->toBe(1)
        ->and($document->storage_path)->not->toBe($oldPath)
        ->and(AuditLog::query()->where('action', AuditAction::SubmissionDocumentReplaced->value)->count())->toBe(2);

    Storage::disk('submission-documents')->assertMissing($oldPath);
    Storage::disk('submission-documents')->assertExists($document->storage_path);
});

it('removes a newly stored file when document persistence fails', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);
    $recordAudit = Mockery::mock(RecordAudit::class);
    $recordAudit->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Simulated audit persistence failure.'));
    $this->app->instance(RecordAudit::class, $recordAudit);
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => fakeSubmissionPdf(),
        ]))->toThrow(RuntimeException::class, 'Simulated audit persistence failure.');

    expect(SubmissionDocument::query()->count())->toBe(0)
        ->and(Storage::disk('submission-documents')->allFiles())->toBe([]);
});

it('requires a document before submitting', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)
        ->postJson(route('public.submissions.submit', $submission))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document');

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Draft)
        ->and($submission->submitted_at)->toBeNull();
});

it('submits atomically and makes metadata, document, submit, and deletion immutable', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)->putJson(route('public.submissions.document.replace', $submission), [
        'document' => fakeSubmissionPdf(),
    ])->assertOk();

    $documentPath = SubmissionDocument::query()->firstOrFail()->storage_path;

    $this->actingAs($user)
        ->postJson(route('public.submissions.submit', $submission))
        ->assertOk()
        ->assertJsonPath('data.status', SubmissionStatus::Submitted->value);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->submitted_at)->not->toBeNull();

    $this->actingAs($user)
        ->patchJson(route('public.submissions.update', $submission), publicSubmissionPayload())
        ->assertConflict();

    $this->actingAs($user)
        ->putJson(route('public.submissions.document.replace', $submission), [
            'document' => fakeSubmissionPdf('pengganti.pdf'),
        ])
        ->assertConflict();

    $this->actingAs($user)
        ->postJson(route('public.submissions.submit', $submission))
        ->assertConflict();

    $this->actingAs($user)
        ->deleteJson(route('public.submissions.destroy', $submission))
        ->assertConflict();

    expect(SubmissionDocument::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::SubmissionSubmitted->value)->count())->toBe(1);

    Storage::disk('submission-documents')->assertExists($documentPath);
    expect(Storage::disk('submission-documents')->allFiles())->toHaveCount(1);
});

it('deletes a draft and its private file while preserving the deletion audit', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)->putJson(route('public.submissions.document.replace', $submission), [
        'document' => fakeSubmissionPdf(),
    ])->assertOk();

    $documentPath = SubmissionDocument::query()->firstOrFail()->storage_path;
    $submissionId = $submission->id;

    $this->actingAs($user)
        ->deleteJson(route('public.submissions.destroy', $submission))
        ->assertNoContent();

    $this->assertDatabaseMissing('letter_submissions', ['id' => $submissionId]);
    $this->assertDatabaseMissing('submission_documents', ['letter_submission_id' => $submissionId]);
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditAction::SubmissionDraftDeleted->value,
        'subject_type' => 'letter_submission',
        'subject_id' => $submissionId,
    ]);
    Storage::disk('submission-documents')->assertMissing($documentPath);
});

it('prevents normal model workflows from changing or deleting audit records', function (): void {
    $submission = createPublicDraft(publicSubmissionUser());
    $audit = AuditLog::query()->where('subject_id', $submission->id)->firstOrFail();

    $audit->action = 'TAMPERED';
    expect(fn () => $audit->save())->toThrow(LogicException::class, 'append-only');

    $freshAudit = $audit->fresh();
    expect(fn () => $freshAudit->delete())->toThrow(LogicException::class, 'append-only');
});

it('prevents deleting an account that is linked to submission history', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)
        ->deleteJson(route('public.submissions.destroy', $submission))
        ->assertNoContent();

    expect($user->letterSubmissions()->exists())->toBeFalse()
        ->and($user->auditLogs()->exists())->toBeTrue();

    $this->actingAs($user)
        ->deleteJson(route('profile.destroy'), ['password' => 'password'])
        ->assertConflict();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => true,
    ]);
});

it('rate limits repeated public submission creation', function (): void {
    $user = publicSubmissionUser();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)
            ->postJson(route('public.submissions.store'), publicSubmissionPayload([
                'external_letter_number' => "EXT/{$attempt}/2026",
            ]))
            ->assertCreated();
    }

    $this->actingAs($user)
        ->postJson(route('public.submissions.store'), publicSubmissionPayload())
        ->assertTooManyRequests();
});

it('renders the public M1 dashboard and submission pages with server owned capabilities', function (): void {
    $user = publicSubmissionUser();
    $submission = createPublicDraft($user);

    $this->actingAs($user)
        ->get(route('public.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Dashboard')
            ->where('summary.total', 1)
            ->where('summary.draft', 1)
            ->where('summary.submitted', 0)
            ->has('recentSubmissions', 1)
            ->where('recentSubmissions.0.public_id', $submission->public_id)
            ->where('recentSubmissions.0.capabilities.can_update', true)
            ->missing('recentSubmissions.0.id')
            ->missing('recentSubmissions.0.document.storage_path')
            ->missing('recentSubmissions.0.document.sha256'));

    $this->actingAs($user)
        ->get(route('public.submissions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/submissions/Create'));

    $this->actingAs($user)
        ->get(route('public.submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/submissions/Index')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.public_id', $submission->public_id)
            ->where('submissions.data.0.capabilities.can_delete', true)
            ->missing('submissions.data.0.id'));

    $this->actingAs($user)
        ->get(route('public.submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/submissions/Show')
            ->where('submission.public_id', $submission->public_id)
            ->where('submission.capabilities.can_submit', false));

    $this->actingAs($user)
        ->get(route('public.submissions.edit', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/submissions/Edit')
            ->where('submission.public_id', $submission->public_id));
});

it('uses Inertia redirects for the browser submission flow and supports spoofed PDF upload', function (): void {
    $user = publicSubmissionUser();

    $this->actingAs($user)
        ->post(route('public.submissions.store'), publicSubmissionPayload())
        ->assertRedirect();

    $submission = LetterSubmission::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('public.submissions.document.replace', $submission), [
            '_method' => 'put',
            'document' => fakeSubmissionPdf(),
        ])
        ->assertRedirect(route('public.submissions.edit', $submission));

    $this->actingAs($user)
        ->post(route('public.submissions.submit', $submission))
        ->assertRedirect(route('public.submissions.show', $submission));

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->document()->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('public.submissions.edit', $submission))
        ->assertConflict();
});

it('keeps browser M1 pages hidden across account and ownership boundaries', function (): void {
    $owner = publicSubmissionUser();
    $otherPublicUser = publicSubmissionUser();
    $internalUser = publicSubmissionUser(['account_type' => AccountType::InternalAccount]);
    $submission = createPublicDraft($owner);

    $this->actingAs($otherPublicUser)
        ->get(route('public.submissions.show', $submission))
        ->assertNotFound();

    $this->actingAs($otherPublicUser)
        ->get(route('public.submissions.edit', $submission))
        ->assertNotFound();

    $this->actingAs($internalUser)
        ->get(route('public.dashboard'))
        ->assertNotFound();

    $this->actingAs($internalUser)
        ->get(route('public.submissions.index'))
        ->assertNotFound();
});
