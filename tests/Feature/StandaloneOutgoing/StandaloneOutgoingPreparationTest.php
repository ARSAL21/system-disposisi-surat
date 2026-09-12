<?php

use App\Actions\AddStandaloneOutgoingDocumentVersion;
use App\Actions\ApproveStandaloneOutgoingWithQr;
use App\Actions\AssignStandaloneOutgoingNumber;
use App\Actions\ChooseStandaloneOutgoingManualSignature;
use App\Actions\CreateStandaloneOutgoingCorrectionDraft;
use App\Actions\CreateStandaloneOutgoingDraft;
use App\Actions\DeliverOutgoingLetter;
use App\Actions\RecordAudit;
use App\Actions\ReviewStandaloneOutgoingDraft;
use App\Actions\ReviewStandaloneOutgoingManualSignatureScan;
use App\Actions\RevokeStandaloneOutgoingDeliveryEmail;
use App\Actions\SubmitStandaloneOutgoingDraft;
use App\Actions\UploadStandaloneOutgoingManualSignatureScan;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\ManualSignatureReviewDecision;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\PermissionName;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\StandaloneOutgoingReviewDecision;
use App\Enums\StandaloneOutgoingReviewStage;
use App\Exceptions\DocumentStorageConflict;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\AuditLog;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterDelivery;
use App\Models\OutgoingLetterDeliveryLink;
use App\Models\OutgoingLetterElectronicApproval;
use App\Models\OutgoingLetterInternalCopyNotification;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\StandaloneOutgoingCopyRecipient;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\OutgoingLetterTemplateStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function standaloneOutgoingPermission(User $user, PermissionName ...$permissions): void
{
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission->value, 'web'));
    }
}

function standaloneOutgoingPdf(): string
{
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Konsep surat keluar mandiri');

    return $pdf->Output('S');
}

/** @return array{staff:User,section:User,assistant:User,unit:OrganizationalUnit,templateVersion:OutgoingLetterTemplateVersion} */
function standaloneOutgoingContext(): array
{
    $parent = standaloneOutgoingUnit('ASISTEN-I', 'Asisten I');
    $unit = standaloneOutgoingUnit('BAGIAN-TEKNIS', 'Bagian Teknis', $parent);

    $staff = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $section = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $assistant = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $assigner = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);

    $staffPosition = standaloneOutgoingPosition(OrganizationCatalog::UNIT_STAFF_LEVEL, 'STAF-TEKNIS', $unit);
    $sectionPosition = standaloneOutgoingPosition(OrganizationCatalog::SECTION_HEAD_LEVEL, 'KABAG-TEKNIS', $unit);
    $assistantPosition = standaloneOutgoingPosition(OrganizationCatalog::ASSISTANT_LEVEL, 'ASISTEN-I', $parent);
    standaloneOutgoingAssignment($staff, $staffPosition, $assigner);
    standaloneOutgoingAssignment($section, $sectionPosition, $assigner);
    standaloneOutgoingAssignment($assistant, $assistantPosition, $assigner);

    standaloneOutgoingPermission($staff, PermissionName::CreateStandaloneOutgoing, PermissionName::ViewStandaloneOutgoing);
    standaloneOutgoingPermission($section, PermissionName::ReviewStandaloneOutgoing, PermissionName::ViewStandaloneOutgoing);
    standaloneOutgoingPermission($assistant, PermissionName::ReviewStandaloneOutgoing, PermissionName::ViewStandaloneOutgoing);

    $template = new OutgoingLetterTemplate;
    $template->organizational_unit_id = $unit->getKey();
    $template->code = 'DINAS';
    $template->name = 'Surat Dinas';
    $template->is_active = true;
    $template->save();

    $templateVersion = new OutgoingLetterTemplateVersion;
    $templateVersion->outgoing_letter_template_id = $template->getKey();
    $templateVersion->version_number = 1;
    $templateVersion->storage_disk = 'outgoing-letter-templates';
    $templateVersion->storage_path = 'units/'.$unit->getKey().'/00000000-0000-4000-8000-000000000001.docx';
    $templateVersion->original_filename = 'template.docx';
    $templateVersion->mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $templateVersion->size_bytes = 128;
    $templateVersion->sha256 = str_repeat('a', 64);
    $templateVersion->qr_page_mode = 'LAST_PAGE';
    $templateVersion->qr_x_ratio = 0.7;
    $templateVersion->qr_y_ratio = 0.8;
    $templateVersion->qr_width_ratio = 0.1;
    $templateVersion->qr_height_ratio = 0.1;
    $templateVersion->uploaded_by_user_id = $section->getKey();
    $templateVersion->uploaded_by_position_assignment_id = PositionAssignment::query()
        ->where('user_id', $section->getKey())
        ->value('id');
    $templateVersion->save();

    return compact('staff', 'section', 'assistant', 'unit', 'templateVersion');
}

function standaloneOutgoingUnit(string $code, string $name, ?OrganizationalUnit $parent = null): OrganizationalUnit
{
    $unit = new OrganizationalUnit;
    $unit->code = $code;
    $unit->name = $name;
    $unit->parent_id = $parent?->getKey();
    $unit->is_active = true;
    $unit->save();

    return $unit;
}

function standaloneOutgoingPosition(string $levelCode, string $code, OrganizationalUnit $unit): Position
{
    $level = PositionLevel::query()->where('code', $levelCode)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = $levelCode;
        $level->name = $levelCode;
        $level->hierarchy_order = match ($levelCode) {
            OrganizationCatalog::ASSISTANT_LEVEL => 30,
            OrganizationCatalog::SECTION_HEAD_LEVEL => 40,
            default => 50,
        };
        $level->is_active = true;
        $level->save();
    }

    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->organizational_unit_id = $unit->getKey();
    $position->code = $code;
    $position->name = $code;
    $position->is_active = true;
    $position->save();

    return $position;
}

function standaloneOutgoingAssignment(User $user, Position $position, User $assigner): PositionAssignment
{
    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subMinute();
    $assignment->assigned_by_user_id = $assigner->getKey();
    $assignment->save();

    return $assignment;
}

/** @return array{draft:StandaloneOutgoingDraft,staff:User,section:User,assistant:User,unit:OrganizationalUnit,assigner:User} */
function standaloneOutgoingApprovedDraft(): array
{
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'],
        $context['unit']->getKey(),
        $context['templateVersion']->getKey(),
        [
            'recipient_name' => 'Direktur PT Contoh', 'recipient_organization' => 'PT Contoh',
            'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null,
            'subject' => 'Undangan rapat koordinasi', 'summary' => 'Konsep undangan rapat.',
        ],
        [],
        UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft);
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['section'], $draft->fresh(), StandaloneOutgoingReviewStage::SectionHead, StandaloneOutgoingReviewDecision::Approved, null,
    );
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['assistant'], $draft->fresh(), StandaloneOutgoingReviewStage::Assistant, StandaloneOutgoingReviewDecision::Approved, null,
    );

    return [
        'draft' => $draft->fresh(), 'staff' => $context['staff'], 'section' => $context['section'],
        'assistant' => $context['assistant'], 'unit' => $context['unit'], 'assigner' => User::query()->firstOrFail(),
    ];
}

/** @return array{officer:User,sekda:User} */
function standaloneOutgoingPublicationActors(User $assigner): array
{
    $generalAffairs = standaloneOutgoingUnit(OrganizationCatalog::GENERAL_AFFAIRS_UNIT, 'Bagian Umum');
    $sekretariat = standaloneOutgoingUnit('SEKRETARIAT-DAERAH', 'Sekretariat Daerah');
    $officer = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $sekda = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $officerPosition = standaloneOutgoingPosition(OrganizationCatalog::GENERAL_AFFAIRS_LEVEL, 'PETUGAS-UMUM', $generalAffairs);
    $sekdaPosition = standaloneOutgoingPosition(OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL, 'SEKDA', $sekretariat);
    standaloneOutgoingAssignment($officer, $officerPosition, $assigner);
    standaloneOutgoingAssignment($sekda, $sekdaPosition, $assigner);
    standaloneOutgoingPermission($officer, PermissionName::NumberOutgoingLetters, PermissionName::DeliverOutgoingLetters, PermissionName::ViewOutgoingRegister);
    standaloneOutgoingPermission($sekda, PermissionName::ApproveStandaloneOutgoing, PermissionName::ViewOutgoingRegister);

    return compact('officer', 'sekda');
}

test('a standalone concept follows staff, section head, then assistant review', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();

    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'],
        $context['unit']->getKey(),
        $context['templateVersion']->getKey(),
        [
            'recipient_name' => 'Direktur PT Contoh', 'recipient_organization' => 'PT Contoh',
            'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null,
            'subject' => 'Undangan rapat koordinasi', 'summary' => 'Konsep undangan rapat.',
        ],
        [Position::query()->where('code', 'KABAG-TEKNIS')->firstOrFail()->getKey()],
        UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );

    expect($draft->status)->toBe(StandaloneOutgoingDraftStatus::Draft);
    expect(StandaloneOutgoingCopyRecipient::query()
        ->where('standalone_outgoing_draft_id', $draft->getKey())
        ->count())->toBe(1);
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft);
    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::SectionReview);

    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['section'], $draft->fresh(), StandaloneOutgoingReviewStage::SectionHead, StandaloneOutgoingReviewDecision::Approved, null,
    );
    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::AssistantReview);

    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['assistant'], $draft->fresh(), StandaloneOutgoingReviewStage::Assistant, StandaloneOutgoingReviewDecision::Approved, null,
    );

    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::AwaitingNumber)
        ->and(AuditLog::query()->where('action', AuditAction::StandaloneOutgoingReviewed->value)->count())->toBe(2);
});

test('an assistant cannot review a draft before its section head has approved it', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft);

    expect(fn () => app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['assistant'], $draft->fresh(), StandaloneOutgoingReviewStage::Assistant, StandaloneOutgoingReviewDecision::Approved, null,
    ))->toThrow(StandaloneOutgoingStateConflict::class);
});

test('an assistant return sends a concept through section review again', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft);
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['section'], $draft->fresh(), StandaloneOutgoingReviewStage::SectionHead, StandaloneOutgoingReviewDecision::Approved, null,
    );
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['assistant'], $draft->fresh(), StandaloneOutgoingReviewStage::Assistant, StandaloneOutgoingReviewDecision::Returned, 'Mohon lengkapi dasar hukum surat.',
    );

    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::RevisionRequired);
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft->fresh());

    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::SectionReview);
});

test('Petugas records one yearly number then the manual-signature path becomes deliverable', function (): void {
    Storage::fake('standalone-outgoing-final-documents');
    $context = standaloneOutgoingApprovedDraft();
    $actors = standaloneOutgoingPublicationActors($context['assigner']);

    $outgoing = app(AssignStandaloneOutgoingNumber::class)->execute(
        $actors['officer'], $context['draft'], '005/1201/SETDA/2026', '2026-09-09',
    );
    expect($outgoing->fresh()->origin)->toBe(OutgoingLetterOrigin::Standalone)
        ->and($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::SekdaReview)
        ->and($context['draft']->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::SekdaReview);

    app(ChooseStandaloneOutgoingManualSignature::class)->execute($actors['sekda'], $outgoing->fresh());
    expect($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::AwaitingManualSignature);

    $scan = app(UploadStandaloneOutgoingManualSignatureScan::class)->execute(
        $context['staff'],
        $outgoing->fresh(),
        UploadedFile::fake()->createWithContent('scan-sekda.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"),
    );
    expect($scan->version_number)->toBe(1)
        ->and($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::ManualScanReview);

    app(ReviewStandaloneOutgoingManualSignatureScan::class)->execute(
        $context['section'], $outgoing->fresh(), ManualSignatureReviewDecision::Verified, 'Scan sesuai nomor dan isi surat.',
    );
    expect($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::ReadyForDelivery);

    app(DeliverOutgoingLetter::class)->execute($context['staff'], $outgoing->fresh(), [
        'delivery_method' => 'IN_PERSON', 'recipient_name' => 'Direktur PT Contoh',
        'delivered_at' => now()->toDateTimeString(), 'tracking_number' => null, 'delivery_note' => 'Diserahkan di kantor penerima.',
    ]);
    expect($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::Delivered)
        ->and(AuditLog::query()->where('action', AuditAction::StandaloneOutgoingDelivered->value)->count())->toBe(1);
});

test('an originating staff member delivers an email-only link and may create an immutable correction draft', function (): void {
    Storage::fake('standalone-outgoing-documents');
    Storage::fake('standalone-outgoing-final-documents');
    $context = standaloneOutgoingApprovedDraft();
    $actors = standaloneOutgoingPublicationActors($context['assigner']);
    DB::table('standalone_outgoing_drafts')
        ->where('id', $context['draft']->getKey())
        ->update(['recipient_email' => 'recipient@example.test']);
    standaloneOutgoingPermission($context['section'], PermissionName::ViewOutgoingRegister);
    $copy = new StandaloneOutgoingCopyRecipient;
    $copy->standalone_outgoing_draft_id = $context['draft']->getKey();
    $copy->position_id = Position::query()->where('code', 'KABAG-TEKNIS')->value('id');
    $copy->save();
    $outgoing = app(AssignStandaloneOutgoingNumber::class)->execute(
        $actors['officer'], $context['draft']->fresh(), '008/1201/SETDA/2026', '2026-09-10',
    );
    app(ChooseStandaloneOutgoingManualSignature::class)->execute($actors['sekda'], $outgoing->fresh());
    app(UploadStandaloneOutgoingManualSignatureScan::class)->execute(
        $context['staff'], $outgoing->fresh(),
        UploadedFile::fake()->createWithContent('scan-sekda.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"),
    );
    app(ReviewStandaloneOutgoingManualSignatureScan::class)->execute(
        $context['section'], $outgoing->fresh(), ManualSignatureReviewDecision::Verified, 'Scan sesuai nomor dan isi surat.',
    );
    app(DeliverOutgoingLetter::class)->execute($context['staff'], $outgoing->fresh(), [
        'delivery_method' => 'EMAIL', 'recipient_name' => null,
        'delivered_at' => null, 'tracking_number' => null, 'delivery_note' => null,
    ]);

    $delivery = OutgoingLetterDelivery::query()->where('outgoing_letter_id', $outgoing->getKey())->firstOrFail();
    expect($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::Delivered)
        ->and($delivery->method->value)->toBe('EMAIL')
        ->and(OutgoingLetterDeliveryLink::query()->where('outgoing_letter_delivery_id', $delivery->getKey())->count())->toBe(1)
        ->and(OutgoingLetterInternalCopyNotification::query()->where('outgoing_letter_delivery_id', $delivery->getKey())->count())->toBe(1);

    $expiredToken = Str::random(64);
    $expiredLink = new OutgoingLetterDeliveryLink;
    $expiredLink->outgoing_letter_delivery_id = $delivery->getKey();
    $expiredLink->token_hash = hash('sha256', $expiredToken);
    $expiredLink->recipient_email = 'recipient@example.test';
    $expiredLink->sent_at = now()->subDays(8);
    $expiredLink->expires_at = now()->subMinute();
    $expiredLink->created_by_user_id = $context['staff']->getKey();
    $expiredLink->created_by_position_assignment_id = PositionAssignment::query()->where('user_id', $context['staff']->getKey())->value('id');
    $expiredLink->save();
    $this->get(route('public.outgoing-letter-delivery-links.download', ['token' => $expiredToken]))->assertNotFound();

    $knownToken = Str::random(64);
    $knownLink = new OutgoingLetterDeliveryLink;
    $knownLink->outgoing_letter_delivery_id = $delivery->getKey();
    $knownLink->token_hash = hash('sha256', $knownToken);
    $knownLink->recipient_email = 'recipient@example.test';
    $knownLink->sent_at = now();
    $knownLink->expires_at = now()->addDays(7);
    $knownLink->created_by_user_id = $context['staff']->getKey();
    $knownLink->created_by_position_assignment_id = PositionAssignment::query()->where('user_id', $context['staff']->getKey())->value('id');
    $knownLink->save();
    $this->get(route('public.outgoing-letter-delivery-links.download', ['token' => $knownToken]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    app(RevokeStandaloneOutgoingDeliveryEmail::class)->execute($context['staff'], $outgoing->fresh());
    $this->get(route('public.outgoing-letter-delivery-links.download', ['token' => $knownToken]))->assertNotFound();

    $correction = app(CreateStandaloneOutgoingCorrectionDraft::class)->execute(
        $context['staff'], $outgoing->fresh(), 'Terdapat koreksi pada rincian jadwal kegiatan.',
    );
    expect($correction->corrects_outgoing_letter_id)->toBe($outgoing->getKey())
        ->and($correction->currentDocumentVersion)->not->toBeNull()
        ->and($outgoing->fresh()->outgoing_number)->toBe('008/1201/SETDA/2026')
        ->and(AuditLog::query()->where('action', AuditAction::StandaloneOutgoingCorrectionDraftCreated->value)->exists())->toBeTrue();
});

test('a conflicting annual number leaves the second standalone draft unchanged', function (): void {
    $first = standaloneOutgoingApprovedDraft();
    $actors = standaloneOutgoingPublicationActors($first['assigner']);
    app(AssignStandaloneOutgoingNumber::class)->execute($actors['officer'], $first['draft'], '006/1201/SETDA/2026', '2026-09-09');

    $secondDraft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $first['staff'],
        $first['unit']->getKey(),
        $first['draft']->outgoing_letter_template_version_id,
        [
            'recipient_name' => 'Direktur PT Lain', 'recipient_organization' => 'PT Lain',
            'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null,
            'subject' => 'Undangan rapat kedua', 'summary' => null,
        ],
        [],
        UploadedFile::fake()->createWithContent('konsep-kedua.pdf', standaloneOutgoingPdf()),
    );
    app(SubmitStandaloneOutgoingDraft::class)->execute($first['staff'], $secondDraft);
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $first['section'], $secondDraft->fresh(), StandaloneOutgoingReviewStage::SectionHead, StandaloneOutgoingReviewDecision::Approved, null,
    );
    app(ReviewStandaloneOutgoingDraft::class)->execute(
        $first['assistant'], $secondDraft->fresh(), StandaloneOutgoingReviewStage::Assistant, StandaloneOutgoingReviewDecision::Approved, null,
    );
    expect(fn () => app(AssignStandaloneOutgoingNumber::class)->execute(
        $actors['officer'], $secondDraft->fresh(), '006/1201/SETDA/2026', '2026-09-09',
    ))->toThrow(ValidationException::class);

    expect($secondDraft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::AwaitingNumber);
});

test('only the Sekda context can create an immutable QR-approved final document', function (): void {
    Storage::fake('standalone-outgoing-final-documents');
    $context = standaloneOutgoingApprovedDraft();
    $actors = standaloneOutgoingPublicationActors($context['assigner']);
    $outgoing = app(AssignStandaloneOutgoingNumber::class)->execute(
        $actors['officer'], $context['draft'], '007/1201/SETDA/2026', '2026-09-09',
    );

    app(ApproveStandaloneOutgoingWithQr::class)->execute($actors['sekda'], $outgoing->fresh());
    $approval = OutgoingLetterElectronicApproval::query()->firstOrFail();

    expect($outgoing->fresh()->status)->toBe(OutgoingLetterStatus::ReadyForDelivery)
        ->and($approval->verification_token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($approval->source_document_sha256)->toBe($context['draft']->fresh()->currentDocumentVersion->sha256)
        ->and($approval->finalDocumentVersion)->not->toBeNull()
        ->and($approval->finalDocumentVersion->sha256)->not->toBe($approval->source_document_sha256);

    $this->actingAs($actors['sekda'])
        ->get(route('back-office.outgoing-letters.standalone.source.preview', [
            $outgoing,
            $context['draft']->fresh()->currentDocumentVersion,
        ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('a missing private concept PDF blocks review without changing the workflow', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    app(SubmitStandaloneOutgoingDraft::class)->execute($context['staff'], $draft);
    $version = $draft->fresh()->currentDocumentVersion;
    expect($version)->not->toBeNull();
    Storage::disk('standalone-outgoing-documents')->delete($version->storage_path);

    expect(fn () => app(ReviewStandaloneOutgoingDraft::class)->execute(
        $context['section'], $draft->fresh(), StandaloneOutgoingReviewStage::SectionHead, StandaloneOutgoingReviewDecision::Approved, null,
    ))->toThrow(DocumentStorageConflict::class);

    expect($draft->fresh()->status)->toBe(StandaloneOutgoingDraftStatus::SectionReview)
        ->and(AuditLog::query()->where('action', AuditAction::StandaloneOutgoingReviewed->value)->count())->toBe(0);
});

test('a failed audit rolls back a new concept version and removes its compensated file', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    $disk = Storage::disk('standalone-outgoing-documents');
    $filesBefore = $disk->allFiles();
    $originalAudit = app(RecordAudit::class);
    $failingAudit = Mockery::mock(RecordAudit::class);
    $failingAudit->shouldReceive('execute')->once()->andThrow(new RuntimeException('Audit storage is unavailable.'));
    app()->instance(RecordAudit::class, $failingAudit);

    try {
        expect(fn () => app(AddStandaloneOutgoingDocumentVersion::class)->execute(
            $context['staff'], $draft->fresh(), UploadedFile::fake()->createWithContent('konsep-revisi.pdf', standaloneOutgoingPdf().'\n% revisi'), 'Memperjelas jadwal kegiatan.',
        ))->toThrow(RuntimeException::class);
    } finally {
        app()->instance(RecordAudit::class, $originalAudit);
    }

    expect($draft->fresh()->documentVersions()->count())->toBe(1)
        ->and($disk->allFiles())->toEqual($filesBefore);
});

test('standalone document history rejects update and deletion', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    $version = $draft->fresh()->currentDocumentVersion;
    expect($version)->not->toBeNull();
    $version->revision_note = 'Tidak boleh diubah.';

    expect(fn () => $version->save())->toThrow(LogicException::class)
        ->and(fn () => $version->delete())->toThrow(LogicException::class);
});

test('a section head from another unit cannot review this unit’s concept', function (): void {
    Storage::fake('standalone-outgoing-documents');
    $context = standaloneOutgoingContext();
    $draft = app(CreateStandaloneOutgoingDraft::class)->execute(
        $context['staff'], $context['unit']->getKey(), $context['templateVersion']->getKey(),
        ['recipient_name' => 'Penerima', 'recipient_organization' => null, 'recipient_position' => null, 'recipient_address' => null, 'recipient_email' => null, 'subject' => 'Perihal konsep surat', 'summary' => null],
        [], UploadedFile::fake()->createWithContent('konsep.pdf', standaloneOutgoingPdf()),
    );
    $otherUnit = standaloneOutgoingUnit('BAGIAN-LAIN', 'Bagian Lain');
    $outsider = User::factory()->internal()->create(['account_type' => AccountType::InternalAccount]);
    $outsiderPosition = standaloneOutgoingPosition(OrganizationCatalog::SECTION_HEAD_LEVEL, 'KABAG-LAIN', $otherUnit);
    standaloneOutgoingAssignment($outsider, $outsiderPosition, User::query()->firstOrFail());
    standaloneOutgoingPermission($outsider, PermissionName::ReviewStandaloneOutgoing, PermissionName::ViewStandaloneOutgoing);

    $response = Gate::forUser($outsider)->inspect('reviewSection', $draft);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

test('template storage rejects a payload that only pretends to be DOCX', function (): void {
    Storage::fake('outgoing-letter-templates');
    $context = standaloneOutgoingContext();

    expect(fn () => app(OutgoingLetterTemplateStorage::class)->store(
        UploadedFile::fake()->createWithContent('template-palsu.docx', 'bukan dokumen Office Open XML'),
        $context['unit']->getKey(),
    ))->toThrow(ValidationException::class);
});
