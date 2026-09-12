<?php

use App\Actions\CompleteDispositionBranch;
use App\Actions\CreateInitialDisposition;
use App\Actions\ForwardDisposition;
use App\Actions\RecordAudit;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\PermissionName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Exceptions\DispositionPositionContextConflict;
use App\Exceptions\DispositionStateConflict;
use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterDocument;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDelivery;
use App\Models\OutgoingLetterDocumentReview;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\SenderOrganization;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Notifications\OfficialResponseAvailable;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Date::setTestNow('2026-09-01 02:00:00');
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
    Storage::fake('letter-response-documents');
    Storage::fake('outgoing-letter-documents');
});

afterEach(function (): void {
    Date::setTestNow();
});

function m6Level(string $code): PositionLevel
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
        OrganizationCatalog::ASSISTANT_LEVEL => 30,
        OrganizationCatalog::SECTION_HEAD_LEVEL => 40,
        default => 90,
    };
    $level->is_active = true;
    $level->save();

    return $level;
}

function m6Unit(string $code, ?string $parentCode = null): OrganizationalUnit
{
    $existing = OrganizationalUnit::query()->where('code', $code)->first();

    $parent = $parentCode === null ? null : m6Unit($parentCode);

    if ($existing instanceof OrganizationalUnit) {
        if ($parent !== null && $existing->parent_id !== $parent->getKey()) {
            $existing->parent_id = $parent->getKey();
            $existing->save();
        }

        return $existing;
    }

    $unit = new OrganizationalUnit;
    $unit->code = $code;
    $unit->name = str_replace('_', ' ', $code);
    $unit->parent_id = $parent?->getKey();
    $unit->is_active = true;
    $unit->save();

    return $unit;
}

function m6Position(string $levelCode, string $name, ?string $unitCode = null): Position
{
    $position = new Position;
    $position->position_level_id = m6Level($levelCode)->getKey();
    $position->organizational_unit_id = $unitCode === null ? null : m6Unit($unitCode)->getKey();
    $position->code = 'M6-'.Str::upper(Str::random(14));
    $position->name = $name;
    $position->is_active = true;
    $position->save();

    return $position;
}

function m6PlacePositionInUnit(
    Position $position,
    string $unitCode,
    ?string $parentUnitCode = null,
): void {
    $position->organizational_unit_id = m6Unit($unitCode, $parentUnitCode)->getKey();
    $position->save();
}

function m6Assignment(User $user, Position $position): PositionAssignment
{
    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->ended_at = null;
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $assignment;
}

function m6Grant(User $user, PermissionName ...$permissions): void
{
    $permissionModels = collect($permissions)->map(
        fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
    );
    $role = Role::findOrCreate('m6-role-'.Str::lower(Str::random(14)));
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);
}

/** @return array{user: User, position: Position, assignment: PositionAssignment} */
function m6Actor(string $levelCode, string $positionName, array $permissions = [], bool $withTwoFactor = false): array
{
    $factory = User::factory()->internal();
    $user = $withTwoFactor ? $factory->withTwoFactor()->create() : $factory->create();
    m6Grant($user, ...$permissions);
    $position = m6Position($levelCode, $positionName);
    $assignment = m6Assignment($user, $position);

    return compact('user', 'position', 'assignment');
}

/** @return array{letter: IncomingLetter, document: LetterDocument, route: LetterRoute} */
function m6RoutedLetter(array $executive, string $subject = 'Koordinasi program prioritas'): array
{
    $owner = User::factory()->create();
    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Instansi '.Str::upper(Str::random(8));
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->external_letter_number = 'EXT/'.Str::upper(Str::random(8));
    $submission->external_letter_date = '2026-08-30';
    $submission->subject = $subject;
    $submission->summary = 'Ringkasan surat disposisi.';
    $submission->submitted_at = now()->subDays(2);
    $submission->save();

    $contents = '%PDF-1.4 position based disposition';
    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $contents);

    $source = new SubmissionDocument;
    $source->letter_submission_id = $submission->getKey();
    $source->storage_disk = 'submission-documents';
    $source->storage_path = $path;
    $source->original_filename = 'surat-disposisi.pdf';
    $source->mime_type = 'application/pdf';
    $source->size_bytes = strlen($contents);
    $source->sha256 = hash('sha256', $contents);
    $source->uploaded_by_user_id = $owner->getKey();
    $source->save();

    $registrar = User::factory()->internal()->create();
    $registrarPosition = m6Position(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Umum',
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );
    $registrarAssignment = m6Assignment($registrar, $registrarPosition);

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
    $letter->received_at = now();
    $letter->status = IncomingLetterStatus::Routed;
    $letter->registered_by_user_id = $registrar->getKey();
    $letter->registered_by_position_assignment_id = $registrarAssignment->getKey();
    $letter->save();

    $document = new LetterDocument;
    $document->incoming_letter_id = $letter->getKey();
    $document->source_submission_document_id = $source->getKey();
    $document->version_number = 1;
    $document->replaces_document_id = null;
    $document->storage_disk = $source->storage_disk;
    $document->storage_path = $source->storage_path;
    $document->original_filename = $source->original_filename;
    $document->mime_type = $source->mime_type;
    $document->size_bytes = $source->size_bytes;
    $document->sha256 = $source->sha256;
    $document->correction_reason = null;
    $document->uploaded_by_user_id = $source->uploaded_by_user_id;
    $document->save();

    $route = new LetterRoute;
    $route->incoming_letter_id = $letter->getKey();
    $route->recipient_position_id = $executive['position']->getKey();
    $route->routed_by_user_id = $registrar->getKey();
    $route->routed_by_position_assignment_id = $registrarAssignment->getKey();
    $route->status = LetterRouteStatus::Pending;
    $route->routed_at = now();
    $route->completed_at = null;
    $route->save();

    return compact('letter', 'document', 'route');
}

test('executive detail exposes only assistant positions and never offers the executive position itself', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::ViewExecutiveInbox, PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten Pemerintahan');
    $selfAssistant = m6Position(OrganizationCatalog::ASSISTANT_LEVEL, 'Pelaksana rangkap Wali Kota');
    m6Assignment($executive['user'], $selfAssistant);
    $inactiveHolderAssistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten dengan akun nonaktif');
    DB::table('users')
        ->where('id', $inactiveHolderAssistant['user']->getKey())
        ->update(['is_active' => false]);
    $vacantAssistant = m6Position(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten tanpa pemegang');
    $sectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Hukum');
    $fixture = m6RoutedLetter($executive);

    $this->actingAs($executive['user'])
        ->get(route('back-office.executive.inbox.show', $fixture['route']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/executive/inbox/Show')
            ->where('auth.capabilities.can_create_dispositions', true)
            ->where('capabilities.can_create_disposition', true)
            ->has('assistantPositions', 1)
            ->where('assistantPositions.0.id', $assistant['position']->getKey())
            ->where('assistantPositions.0.level_code', OrganizationCatalog::ASSISTANT_LEVEL)
            ->where('assistantPositions.0.is_available', true)
            ->has('instructionLabels', 7)
            ->where('firstDisposition', null)
            ->where('routes.store', route('back-office.executive.inbox.dispositions.store', $fixture['route']))
            ->where('assistantPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->doesntContain($executive['position']->getKey())
                && collect($positions)->pluck('id')->doesntContain($selfAssistant->getKey())
                && collect($positions)
                    ->pluck('id')
                    ->doesntContain($inactiveHolderAssistant['position']->getKey())
                && collect($positions)->pluck('id')->doesntContain($vacantAssistant->getKey())
                && collect($positions)->pluck('id')->doesntContain($sectionHead['position']->getKey())),
        );
});

test('executive creates an atomic first disposition and assistant receives only its own branch', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [
            PermissionName::ViewExecutiveInbox,
            PermissionName::CreateDispositions,
            PermissionName::ViewLetterActivities,
        ],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Administrasi Umum',
        [PermissionName::ViewDispositions],
    );
    $otherAssistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Pemerintahan',
        [PermissionName::ViewDispositions],
    );
    $fixture = m6RoutedLetter($executive);
    $labels = InstructionLabel::query()->orderBy('sort_order')->take(2)->get();

    $this->actingAs($executive['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
            'recipient_position_ids' => [$assistant['position']->getKey()],
            'instruction_label_ids' => $labels->modelKeys(),
            'instruction_note' => 'Mohon ditelaah dan dikoordinasikan.',
        ])
        ->assertRedirect(route('back-office.executive.inbox.show', $fixture['route']));

    $disposition = Disposition::query()->firstOrFail();
    $recipient = DispositionRecipient::query()->firstOrFail();
    expect($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Completed)
        ->and($fixture['route']->completed_at)->not->toBeNull()
        ->and($disposition->source_route_id)->toBe($fixture['route']->getKey())
        ->and($disposition->parent_recipient_id)->toBeNull()
        ->and($disposition->created_by_user_id)->toBe($executive['user']->getKey())
        ->and($disposition->created_by_position_assignment_id)->toBe($executive['assignment']->getKey())
        ->and($recipient->recipient_position_id)->toBe($assistant['position']->getKey())
        ->and($recipient->status)->toBe(DispositionRecipientStatus::Pending)
        ->and($disposition->instructionLabels()->count())->toBe(2);

    $audit = AuditLog::query()
        ->where('action', AuditAction::DispositionCreated->value)
        ->where('subject_type', 'disposition')
        ->where('subject_id', $disposition->getKey())
        ->firstOrFail();
    expect($audit->actor_position_assignment_id)->toBe($executive['assignment']->getKey())
        ->and($audit->new_values['letter_status'])->toBe(IncomingLetterStatus::InProgress->value)
        ->and($audit->new_values['route_status'])->toBe(LetterRouteStatus::Completed->value)
        ->and($audit->metadata['recipient_ids'])->toBe([$recipient->getKey()]);

    $this->actingAs($executive['user'])
        ->get(route('back-office.letter-activities.index', [
            'action' => AuditAction::DispositionCreated->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.action', AuditAction::DispositionCreated->value)
            ->where('activities.data.0.target.agenda_number', $fixture['letter']->agenda_number)
            ->where('activities.data.0.target.subject', $fixture['letter']->subject)
            ->where('activities.data.0.after.status_surat', 'Dalam proses')
            ->where('activities.data.0.after.status_routing', 'Routing selesai')
            ->where('activities.data.0.after.status_penerima_disposisi', 'Menunggu ditangani'));

    $this->actingAs($executive['user'])
        ->get(route('back-office.executive.inbox.show', $fixture['route']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.can_create_disposition', false)
            ->where('firstDisposition.recipients.0.recipient_position.id', $assistant['position']->getKey())
            ->where('firstDisposition.recipients.0.status', DispositionRecipientStatus::Pending->value)
            ->has('assistantPositions', 0)
            ->missing('firstDisposition.disposed_by.email'));

    $this->actingAs($executive['user'])
        ->postJson(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
            'recipient_position_ids' => [$assistant['position']->getKey()],
            'instruction_label_ids' => $labels->modelKeys(),
            'instruction_note' => '',
        ])
        ->assertConflict();

    $otherFixture = m6RoutedLetter($executive, 'Surat khusus Asisten Pemerintahan');
    app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $otherFixture['route'],
        [$otherAssistant['position']->getKey()],
        [$labels->firstOrFail()->getKey()],
        null,
    );

    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/dispositions/inbox/Index')
            ->has('inbox.data', 1)
            ->where('inbox.data.0.recipient_id', $recipient->getKey())
            ->where('inbox.data.0.letter.id', $fixture['letter']->getKey())
            ->where('summary.pending', 1)
            ->where('summary.in_progress', 0)
            ->where('summary.received_today', 1)
            ->where('auth.capabilities.can_view_dispositions', true)
            ->missing('inbox.data.0.current_document.storage_disk')
            ->missing('inbox.data.0.current_document.storage_path')
            ->missing('inbox.data.0.letter.current_document.storage_disk')
            ->missing('inbox.data.0.letter.current_document.storage_path')
            ->missing('inbox.data.0.sender.email'));

    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.index', [
            'search' => 'Surat khusus Asisten Pemerintahan',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inbox.data', 0)
            ->where('summary.pending', 1));

    $preview = $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.document.preview', $recipient));
    $preview
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $download = $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.document.download', $recipient));
    $download
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($download->headers->get('Content-Disposition'))->toStartWith('attachment;');

    $this->actingAs($otherAssistant['user'])
        ->get(route('back-office.dispositions.inbox.show', $recipient))
        ->assertNotFound();
    $this->actingAs($otherAssistant['user'])
        ->get(route('back-office.dispositions.inbox.document.preview', $recipient))
        ->assertNotFound();

    Storage::disk($fixture['document']->storage_disk)->delete($fixture['document']->storage_path);
    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.document.preview', $recipient))
        ->assertConflict();
});

test('executive can appoint multiple assistants in one atomic first disposition', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::ViewExecutiveInbox, PermissionName::CreateDispositions],
    );
    $assistants = [
        m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten I', [PermissionName::ViewDispositions]),
        m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten II', [PermissionName::ViewDispositions]),
    ];
    $fixture = m6RoutedLetter($executive, 'Disposisi lintas dua Asisten');
    $label = InstructionLabel::query()->firstOrFail();
    $recipientPositionIds = array_map(
        static fn (array $assistant): int => (int) $assistant['position']->getKey(),
        $assistants,
    );

    $this->actingAs($executive['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
            'recipient_position_ids' => array_reverse($recipientPositionIds),
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => 'Koordinasikan sesuai ruang lingkup masing-masing.',
        ])
        ->assertRedirect(route('back-office.executive.inbox.show', $fixture['route']));

    $disposition = Disposition::query()->firstOrFail();
    $recipients = $disposition->recipients()->orderBy('recipient_position_id')->get();
    expect($recipients)->toHaveCount(2)
        ->and($recipients->pluck('recipient_position_id')->all())->toBe($recipientPositionIds)
        ->and($recipients->pluck('status')->all())->each->toBe(DispositionRecipientStatus::Pending)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Completed)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress);

    $audit = AuditLog::query()
        ->where('action', AuditAction::DispositionCreated->value)
        ->where('subject_id', $disposition->getKey())
        ->firstOrFail();
    expect($audit->new_values['recipient_position_ids'])->toBe($recipientPositionIds)
        ->and($audit->metadata['recipient_ids'])->toBe($recipients->modelKeys());

    $this->actingAs($executive['user'])
        ->get(route('back-office.executive.inbox.show', $fixture['route']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('firstDisposition.recipients', 2)
            ->where('firstDisposition.recipients.0.recipient_position.id', $recipientPositionIds[0])
            ->where('firstDisposition.recipients.1.recipient_position.id', $recipientPositionIds[1])
            ->missing('firstDisposition.recipients.0.recipient_position.active_assignment_id'));

    foreach ($assistants as $assistant) {
        $this->actingAs($assistant['user'])
            ->get(route('back-office.dispositions.inbox.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('inbox.data', 1)
                ->where('inbox.data.0.recipient_position.id', $assistant['position']->getKey()));
    }
});

test('permission and position boundaries preserve 403 versus hidden 404 responses', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::ViewExecutiveInbox],
    );
    $otherExecutive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $staff = m6Actor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        'Petugas Surat',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten');
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();
    $payload = [
        'recipient_position_ids' => [$assistant['position']->getKey()],
        'instruction_label_ids' => [$label->getKey()],
        'instruction_note' => '',
    ];

    $this->actingAs($executive['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), $payload)
        ->assertForbidden();
    $this->actingAs($otherExecutive['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), $payload)
        ->assertNotFound();
    $this->actingAs($staff['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), $payload)
        ->assertNotFound();

    $technicalAdministrator = User::factory()->internal()->create();
    m6Grant($technicalAdministrator, ...PermissionName::cases());
    $this->actingAs($technicalAdministrator)
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), $payload)
        ->assertNotFound();
    $this->actingAs($technicalAdministrator)
        ->get(route('back-office.dispositions.inbox.index'))
        ->assertNotFound();

    expect(Disposition::query()->count())->toBe(0)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed);

    $unprivilegedAssistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten tanpa izin');
    $this->actingAs($unprivilegedAssistant['user'])
        ->get(route('back-office.dispositions.inbox.index'))
        ->assertForbidden();

    m6Grant($executive['user'], PermissionName::ViewDispositions);
    $this->actingAs($executive['user'])
        ->get(route('back-office.dispositions.inbox.index'))
        ->assertNotFound();
});

test('invalid hierarchy self selection vacant targets and inactive labels are rejected without mutation', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten Aktif');
    $inactiveAssistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten Nonaktif');
    DB::table('users')
        ->where('id', $inactiveAssistant['user']->getKey())
        ->update(['is_active' => false]);
    $selfAssistant = m6Position(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten rangkap actor');
    m6Assignment($executive['user'], $selfAssistant);
    $vacantAssistant = m6Position(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten Kosong');
    $sectionHead = m6Position(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian');
    $fixture = m6RoutedLetter($executive);
    $activeLabel = InstructionLabel::query()->firstOrFail();
    $inactiveLabel = InstructionLabel::query()
        ->where('id', '!=', $activeLabel->getKey())
        ->firstOrFail();
    $inactiveLabel->is_active = false;
    $inactiveLabel->save();

    foreach ([
        [$executive['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$selfAssistant->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$sectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$vacantAssistant->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$inactiveAssistant['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$assistant['position']->getKey(), [$inactiveLabel->getKey()], 'instruction_label_ids'],
    ] as [$positionId, $labelIds, $errorKey]) {
        $this->actingAs($executive['user'])
            ->from(route('back-office.executive.inbox.show', $fixture['route']))
            ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
                'recipient_position_ids' => [$positionId],
                'instruction_label_ids' => $labelIds,
                'instruction_note' => '',
            ])
            ->assertSessionHasErrors($errorKey);
    }

    expect(Disposition::query()->count())->toBe(0)
        ->and(DispositionRecipient::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(0)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed);
});

test('disposition creation rejects corrupt official document metadata without partial state changes', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten');
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();

    DB::table('letter_documents')
        ->where('id', $fixture['document']->getKey())
        ->update(['storage_path' => '../dokumen-tidak-sah.pdf']);

    $this->actingAs($executive['user'])
        ->postJson(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
            'recipient_position_ids' => [$assistant['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => '',
        ])
        ->assertConflict();

    expect(Disposition::query()->count())->toBe(0)
        ->and(DispositionRecipient::query()->count())->toBe(0)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->exists())
        ->toBeFalse();
});

test('disposition action rechecks stale state and rolls back all database changes when audit fails', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten');
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();

    DB::table('users')
        ->where('id', $executive['user']->getKey())
        ->update(['is_active' => false]);
    expect(fn () => app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    ))->toThrow(DispositionPositionContextConflict::class);
    DB::table('users')
        ->where('id', $executive['user']->getKey())
        ->update(['is_active' => true]);
    $executive['user']->refresh();

    $fixture['route']->status = LetterRouteStatus::Completed;
    $fixture['route']->completed_at = now();
    $fixture['route']->save();

    expect(fn () => app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    ))->toThrow(DispositionStateConflict::class);

    $fixture['route']->refresh();
    DB::table('letter_routes')->where('id', $fixture['route']->getKey())->update([
        'status' => LetterRouteStatus::Pending->value,
        'completed_at' => null,
    ]);

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new RuntimeException('Simulated disposition audit failure.'));
    });

    expect(fn () => app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route']->refresh(),
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    ))->toThrow(RuntimeException::class, 'Simulated disposition audit failure.');

    expect(Disposition::query()->count())->toBe(0)
        ->and(DispositionRecipient::query()->count())->toBe(0)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed);
});

test('disposition records are immutable and recipient identity cannot be rewritten', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten');
    $otherAssistant = m6Actor(OrganizationCatalog::ASSISTANT_LEVEL, 'Asisten Lain');
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();
    $disposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $recipient = $disposition->recipients()->firstOrFail();

    expect(fn () => $disposition->forceFill(['instruction_note' => 'ubah'])->save())
        ->toThrow(LogicException::class)
        ->and(fn () => $disposition->fresh()->delete())
        ->toThrow(LogicException::class)
        ->and(fn () => $disposition->instructionLabels()->detach($label->getKey()))
        ->toThrow(LogicException::class)
        ->and(fn () => $recipient->forceFill([
            'recipient_position_id' => $otherAssistant['position']->getKey(),
        ])->save())
        ->toThrow(LogicException::class)
        ->and(fn () => $recipient->fresh()->delete())
        ->toThrow(LogicException::class);

    expect($disposition->instructionLabels()->count())->toBe(1);

    $recipient = $recipient->fresh();
    expect(fn () => $recipient->forceFill([
        'status' => DispositionRecipientStatus::InProgress,
        'started_at' => now(),
        'completion_note' => 'Metadata selesai ditulis terlalu dini.',
    ])->save())->toThrow(LogicException::class);
});

test('instruction label catalog is permission protected audited and keeps one active label', function (): void {
    $viewer = User::factory()->internal()->create();
    m6Grant($viewer, PermissionName::ViewDispositionInstructions);
    $manager = User::factory()->internal()->withTwoFactor()->create();
    m6Grant(
        $manager,
        PermissionName::ViewDispositionInstructions,
        PermissionName::ManageDispositionInstructions,
    );
    $managerWithoutMfa = User::factory()->internal()->create();
    m6Grant($managerWithoutMfa, PermissionName::ManageDispositionInstructions);

    $this->actingAs(User::factory()->internal()->create())
        ->get(route('back-office.workflow.instruction-labels.index'))
        ->assertForbidden();
    $this->actingAs($viewer)
        ->get(route('back-office.workflow.instruction-labels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/workflow/instruction-labels/Index')
            ->has('labels', 7)
            ->where('activeLabelCount', 7)
            ->where('mutationSecurity.can_manage', false));

    $this->actingAs($managerWithoutMfa)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('back-office.workflow.instruction-labels.store'), [
            'code' => 'TIDAK_TERSIMPAN',
            'name' => 'Tidak tersimpan',
            'description' => null,
            'sort_order' => 80,
        ])
        ->assertRedirect(route('security.edit'));

    $this->actingAs($manager)
        ->withSession(['auth.password_confirmed_at' => 0])
        ->post(route('back-office.workflow.instruction-labels.store'), [
            'code' => 'JUGA_TIDAK_TERSIMPAN',
            'name' => 'Juga tidak tersimpan',
            'description' => null,
            'sort_order' => 80,
        ])
        ->assertRedirect(route('back-office.password.confirm'));

    expect(InstructionLabel::query()
        ->whereIn('code', ['TIDAK_TERSIMPAN', 'JUGA_TIDAK_TERSIMPAN'])
        ->exists())->toBeFalse();

    $session = ['auth.password_confirmed_at' => now()->getTimestamp()];
    $this->actingAs($manager)
        ->from(route('back-office.workflow.instruction-labels.index'))
        ->withSession($session)
        ->post(route('back-office.workflow.instruction-labels.store'), [
            'code' => '  VERIFIKASI_DATA ',
            'name' => ' Verifikasi data ',
            'description' => ' Pastikan data pendukung lengkap. ',
            'sort_order' => 80,
        ])
        ->assertRedirect(route('back-office.workflow.instruction-labels.index'))
        ->assertSessionHasNoErrors();

    $label = InstructionLabel::query()->where('code', 'VERIFIKASI_DATA')->firstOrFail();
    $this->actingAs($manager)
        ->withSession($session)
        ->patch(route('back-office.workflow.instruction-labels.update', $label), [
            'code' => $label->code,
            'name' => 'Verifikasi dokumen',
            'description' => '',
            'sort_order' => 85,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $this->actingAs($manager)
        ->withSession($session)
        ->patch(route('back-office.workflow.instruction-labels.status', $label), [
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($label->refresh()->name)->toBe('Verifikasi dokumen')
        ->and($label->description)->toBeNull()
        ->and($label->sort_order)->toBe(85)
        ->and($label->is_active)->toBeFalse()
        ->and(AuditLog::query()->where('subject_type', 'instruction_label')->count())->toBe(3);

    $lastActive = InstructionLabel::query()
        ->where('is_active', true)
        ->orderBy('id')
        ->firstOrFail();
    DB::table('instruction_labels')
        ->where('id', '!=', $lastActive->getKey())
        ->update(['is_active' => false]);
    $this->actingAs($manager)
        ->withSession($session)
        ->patchJson(route('back-office.workflow.instruction-labels.status', $lastActive), [
            'is_active' => false,
        ])
        ->assertConflict();
    expect($lastActive->refresh()->is_active)->toBeTrue();
});

test('disposition input and mutation rate limits are enforced before state changes', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::CreateDispositions],
    );
    $fixture = m6RoutedLetter($executive);

    $this->actingAs($executive['user'])
        ->getJson(route('back-office.executive.inbox.dispositions.store', $fixture['route']))
        ->assertMethodNotAllowed();

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->actingAs($executive['user'])
            ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [])
            ->assertSessionHasErrors([
                'recipient_position_ids',
                'instruction_label_ids',
            ]);
    }

    $this->actingAs($executive['user'])
        ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [])
        ->assertTooManyRequests();

    expect(Disposition::query()->count())->toBe(0)
        ->and($fixture['route']->refresh()->status)->toBe(LetterRouteStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed);
});

test('assistant forwards one atomic disposition to multiple section heads and each head sees only its own branch', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Perekonomian',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHeadOne = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Hukum',
        [PermissionName::ViewDispositions],
    );
    $sectionHeadTwo = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Perekonomian',
        [PermissionName::ViewDispositions],
    );
    $otherSectionHead = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Tidak Terkait',
        [PermissionName::ViewDispositions],
    );
    $selfHeldSectionHead = m6Position(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian rangkap Asisten');
    m6Assignment($assistant['user'], $selfHeldSectionHead);
    m6PlacePositionInUnit($assistant['position'], 'M6-ASISTEN-PEREKONOMIAN');
    m6PlacePositionInUnit($sectionHeadOne['position'], 'M6-BAGIAN-HUKUM', 'M6-ASISTEN-PEREKONOMIAN');
    m6PlacePositionInUnit($sectionHeadTwo['position'], 'M6-BAGIAN-PEREKONOMIAN', 'M6-ASISTEN-PEREKONOMIAN');
    $fixture = m6RoutedLetter($executive);
    $labels = InstructionLabel::query()->orderBy('sort_order')->take(2)->get();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$labels->firstOrFail()->getKey()],
        'Mohon ditangani pada tingkat Asisten.',
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();

    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.can_forward_disposition', true)
            ->has('instructionLabels', 7)
            ->where('routes.store', route('back-office.dispositions.inbox.forward.store', $assistantRecipient))
            ->where('sectionHeadPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->contains($sectionHeadOne['position']->getKey())
                && collect($positions)->pluck('id')->contains($sectionHeadTwo['position']->getKey())
                && collect($positions)->pluck('id')->doesntContain($selfHeldSectionHead->getKey())));

    $this->actingAs($assistant['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
            'recipient_position_ids' => [
                $sectionHeadTwo['position']->getKey(),
                $sectionHeadOne['position']->getKey(),
            ],
            'instruction_label_ids' => $labels->modelKeys(),
            'instruction_note' => 'Koordinasikan tindak lanjut bersama unit terkait.',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $assistantRecipient));

    $childDisposition = Disposition::query()
        ->where('parent_recipient_id', $assistantRecipient->getKey())
        ->firstOrFail();
    $sectionRecipients = $childDisposition->recipients()->orderBy('recipient_position_id')->get();

    expect($childDisposition->source_route_id)->toBeNull()
        ->and($childDisposition->incoming_letter_id)->toBe($fixture['letter']->getKey())
        ->and($childDisposition->created_by_user_id)->toBe($assistant['user']->getKey())
        ->and($childDisposition->created_by_position_assignment_id)->toBe($assistant['assignment']->getKey())
        ->and($childDisposition->instructionLabels()->pluck('id')->all())->toEqualCanonicalizing($labels->modelKeys())
        ->and($sectionRecipients)->toHaveCount(2)
        ->and($sectionRecipients->pluck('recipient_position_id')->all())->toEqualCanonicalizing([
            $sectionHeadOne['position']->getKey(),
            $sectionHeadTwo['position']->getKey(),
        ])
        ->and($sectionRecipients->pluck('status')->all())->each->toBe(DispositionRecipientStatus::Pending);

    expect($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($assistantRecipient->completed_by_user_id)->toBe($assistant['user']->getKey())
        ->and($assistantRecipient->completed_by_position_assignment_id)->toBe($assistant['assignment']->getKey())
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress);

    $audit = AuditLog::query()
        ->where('action', AuditAction::DispositionCreated->value)
        ->where('subject_id', $childDisposition->getKey())
        ->firstOrFail();
    expect($audit->actor_position_assignment_id)->toBe($assistant['assignment']->getKey())
        ->and($audit->new_values['parent_recipient_status'])->toBe(DispositionRecipientStatus::Completed->value)
        ->and($audit->new_values['recipient_position_ids'])->toEqual([
            $sectionHeadOne['position']->getKey(),
            $sectionHeadTwo['position']->getKey(),
        ])
        ->and($audit->metadata['parent_recipient_id'])->toBe($assistantRecipient->getKey())
        ->and($audit->metadata['recipient_ids'])->toHaveCount(2)
        ->and($audit->metadata['hierarchy']['rule'])->toBe('DIRECT_CHILD_UNIT')
        ->and($audit->metadata['hierarchy']['source_assistant']['position_id'])->toBe($assistant['position']->getKey())
        ->and($audit->metadata['hierarchy']['recipients'])->toHaveCount(2);

    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.can_forward_disposition', false)
            ->has('forwardedDisposition.recipients', 2)
            ->missing('routes.store')
            ->missing('forwardedDisposition.disposed_by.email'));

    $firstSectionRecipient = $sectionRecipients
        ->firstWhere('recipient_position_id', $sectionHeadOne['position']->getKey());
    $this->actingAs($sectionHeadOne['user'])
        ->get(route('back-office.dispositions.inbox.show', $firstSectionRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.can_forward_disposition', false)
            ->where('disposition.recipient_position.level_code', OrganizationCatalog::SECTION_HEAD_LEVEL)
            ->has('sectionHeadPositions', 0)
            ->missing('routes.store'));

    $this->actingAs($otherSectionHead['user'])
        ->get(route('back-office.dispositions.inbox.show', $firstSectionRecipient))
        ->assertNotFound();

    $this->actingAs($assistant['user'])
        ->postJson(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
            'recipient_position_ids' => [$sectionHeadOne['position']->getKey()],
            'instruction_label_ids' => [$labels->firstOrFail()->getKey()],
            'instruction_note' => '',
        ])
        ->assertConflict();
});

test('multiple-recipient forwarding preserves permission, position, and target boundaries', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Pemerintahan',
        [PermissionName::ViewDispositions],
    );
    $sectionHead = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Pemerintahan',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();
    $payload = [
        'recipient_position_ids' => [$sectionHead['position']->getKey()],
        'instruction_label_ids' => [$label->getKey()],
        'instruction_note' => '',
    ];

    $this->actingAs($assistant['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), $payload)
        ->assertForbidden();
    $this->actingAs($sectionHead['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), $payload)
        ->assertNotFound();

    $technicalAdministrator = User::factory()->internal()->create();
    m6Grant(
        $technicalAdministrator,
        PermissionName::ViewDispositions,
        PermissionName::CreateDispositions,
    );
    $this->actingAs($technicalAdministrator)
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), $payload)
        ->assertNotFound();

    expect(Disposition::query()->count())->toBe(1)
        ->and($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

test('an assistant only sees and can appoint section heads in its direct organizational scope', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $firstAssistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten I',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $secondAssistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten II',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Ekonomi',
        [PermissionName::ViewDispositions],
    );
    m6PlacePositionInUnit($firstAssistant['position'], 'M6-ASISTEN-I');
    m6PlacePositionInUnit($secondAssistant['position'], 'M6-ASISTEN-II');
    m6PlacePositionInUnit($sectionHead['position'], 'M6-BAGIAN-EKONOMI', 'M6-ASISTEN-I');
    $fixture = m6RoutedLetter($executive, 'Koordinasi ekonomi lintas Asisten');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$firstAssistant['position']->getKey(), $secondAssistant['position']->getKey()],
        [$label->getKey()],
        'Pelajari dan teruskan kepada Bagian yang paling relevan.',
    );
    $assistantRecipients = $initialDisposition->recipients()
        ->get()
        ->keyBy('recipient_position_id');
    $firstRecipient = $assistantRecipients->get($firstAssistant['position']->getKey());
    $secondRecipient = $assistantRecipients->get($secondAssistant['position']->getKey());

    if (! $firstRecipient instanceof DispositionRecipient
        || ! $secondRecipient instanceof DispositionRecipient) {
        throw new RuntimeException('Fixture penerima Asisten tidak lengkap.');
    }

    $this->actingAs($firstAssistant['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $firstRecipient), [
            'recipient_position_ids' => [$sectionHead['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => 'Siapkan telaahan teknis bidang ekonomi.',
        ])
        ->assertRedirect();

    $this->actingAs($secondAssistant['user'])
        ->get(route('back-office.dispositions.inbox.show', $secondRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.can_forward_disposition', true)
            ->where('sectionHeadPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->doesntContain($sectionHead['position']->getKey())));

    $this->actingAs($secondAssistant['user'])
        ->from(route('back-office.dispositions.inbox.show', $secondRecipient))
        ->post(route('back-office.dispositions.inbox.forward.store', $secondRecipient), [
            'recipient_position_ids' => [$sectionHead['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => 'Crafted request tidak boleh melewati validasi server.',
        ])
        ->assertNotFound();

    expect(Disposition::query()->whereNotNull('parent_recipient_id')->count())->toBe(1)
        ->and(DispositionRecipient::query()
            ->where('recipient_position_id', $sectionHead['position']->getKey())
            ->count())->toBe(1)
        ->and($secondRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending);
});

test('direct-child target scope is isolated per assistant and revalidated before mutation', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistantOne = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten I',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $assistantTwo = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten II',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $assistantThree = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten III',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $headOne = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Pemerintahan');
    $headTwo = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Hukum');
    $headThree = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Kesra');
    $headFour = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Perekonomian');
    $headFive = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Organisasi');

    m6PlacePositionInUnit($assistantOne['position'], 'M6-SCOPE-ASISTEN-I');
    m6PlacePositionInUnit($assistantTwo['position'], 'M6-SCOPE-ASISTEN-II');
    m6PlacePositionInUnit($assistantThree['position'], 'M6-SCOPE-ASISTEN-III');
    m6PlacePositionInUnit($headOne['position'], 'M6-SCOPE-PEMERINTAHAN', 'M6-SCOPE-ASISTEN-I');
    m6PlacePositionInUnit($headTwo['position'], 'M6-SCOPE-HUKUM', 'M6-SCOPE-ASISTEN-I');
    m6PlacePositionInUnit($headThree['position'], 'M6-SCOPE-KESRA', 'M6-SCOPE-ASISTEN-I');
    m6PlacePositionInUnit($headFour['position'], 'M6-SCOPE-PEREKONOMIAN', 'M6-SCOPE-ASISTEN-II');
    m6PlacePositionInUnit($headFive['position'], 'M6-SCOPE-ORGANISASI', 'M6-SCOPE-ASISTEN-III');

    $fixture = m6RoutedLetter($executive, 'Validasi struktur disposisi Asisten');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [
            $assistantOne['position']->getKey(),
            $assistantTwo['position']->getKey(),
            $assistantThree['position']->getKey(),
        ],
        [$label->getKey()],
        null,
    );
    $recipients = $initialDisposition->recipients()->get()->keyBy('recipient_position_id');
    $firstRecipient = $recipients->get($assistantOne['position']->getKey());
    $secondRecipient = $recipients->get($assistantTwo['position']->getKey());
    $thirdRecipient = $recipients->get($assistantThree['position']->getKey());

    if (! $firstRecipient instanceof DispositionRecipient
        || ! $secondRecipient instanceof DispositionRecipient
        || ! $thirdRecipient instanceof DispositionRecipient) {
        throw new RuntimeException('Fixture recipient Asisten tidak lengkap.');
    }

    $this->actingAs($assistantOne['user'])
        ->get(route('back-office.dispositions.inbox.show', $firstRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sectionHeadPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([
                    $headOne['position']->getKey(),
                    $headTwo['position']->getKey(),
                    $headThree['position']->getKey(),
                ])->sort()->values()->all())
            ->missing('sectionHeadPositions.0.assignment_id')
            ->missing('sectionHeadPositions.0.holder_email'));

    $this->actingAs($assistantTwo['user'])
        ->get(route('back-office.dispositions.inbox.show', $secondRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sectionHeadPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->all() === [$headFour['position']->getKey()]));

    $this->actingAs($assistantThree['user'])
        ->get(route('back-office.dispositions.inbox.show', $thirdRecipient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sectionHeadPositions', fn ($positions): bool => collect($positions)
                ->pluck('id')
                ->all() === [$headFive['position']->getKey()]));

    DB::table('organizational_units')
        ->where('id', $headOne['position']->organizational_unit_id)
        ->update(['parent_id' => $assistantTwo['position']->organizational_unit_id]);

    $this->actingAs($assistantOne['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $firstRecipient), [
            'recipient_position_ids' => [$headOne['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => 'Target yang sudah berpindah unit tidak boleh diteruskan.',
        ])
        ->assertNotFound();

    expect(Disposition::query()->whereNotNull('parent_recipient_id')->count())->toBe(0)
        ->and(DispositionRecipient::query()->count())->toBe(3)
        ->and($firstRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

test('historical duplicate section head branches can still complete with separate technical materials', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $firstAssistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten I',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $secondAssistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten II',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Ekonomi',
        [PermissionName::ViewDispositions, PermissionName::ProcessDispositions],
    );
    m6PlacePositionInUnit($firstAssistant['position'], 'M6-ASISTEN-I');
    m6PlacePositionInUnit($secondAssistant['position'], 'M6-ASISTEN-II');
    m6PlacePositionInUnit($sectionHead['position'], 'M6-BAGIAN-EKONOMI', 'M6-ASISTEN-I');
    $fixture = m6RoutedLetter($executive, 'Surat lama dengan penerima Bagian ganda');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$firstAssistant['position']->getKey(), $secondAssistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $assistantRecipients = $initialDisposition->recipients()
        ->get()
        ->keyBy('recipient_position_id');
    $firstAssistantRecipient = $assistantRecipients->get($firstAssistant['position']->getKey());
    $secondAssistantRecipient = $assistantRecipients->get($secondAssistant['position']->getKey());

    if (! $firstAssistantRecipient instanceof DispositionRecipient
        || ! $secondAssistantRecipient instanceof DispositionRecipient) {
        throw new RuntimeException('Fixture penerima Asisten tidak lengkap.');
    }

    $firstChildDisposition = app(ForwardDisposition::class)->execute(
        $firstAssistant['user'],
        $firstAssistantRecipient,
        [$sectionHead['position']->getKey()],
        [$label->getKey()],
        'Cabang pertama yang sudah tercatat sebelum invariant diperketat.',
    );
    $firstBranch = $firstChildDisposition->recipients()->firstOrFail();

    $historicalDisposition = new Disposition;
    $historicalDisposition->incoming_letter_id = $fixture['letter']->getKey();
    $historicalDisposition->source_route_id = null;
    $historicalDisposition->parent_recipient_id = $secondAssistantRecipient->getKey();
    $historicalDisposition->created_by_user_id = $secondAssistant['user']->getKey();
    $historicalDisposition->created_by_position_assignment_id = $secondAssistant['assignment']->getKey();
    $historicalDisposition->instruction_note = 'Data historis sebelum validasi recipient lintas Asisten tersedia.';
    $historicalDisposition->created_at = now();
    $historicalDisposition->save();
    $historicalDisposition->instructionLabels()->attach([$label->getKey()]);

    $historicalBranch = new DispositionRecipient;
    $historicalBranch->disposition_id = $historicalDisposition->getKey();
    $historicalBranch->recipient_position_id = $sectionHead['position']->getKey();
    $historicalBranch->status = DispositionRecipientStatus::Pending;
    $historicalBranch->received_at = now();
    $historicalBranch->started_at = null;
    $historicalBranch->completed_at = null;
    $historicalBranch->completed_by_user_id = null;
    $historicalBranch->completed_by_position_assignment_id = null;
    $historicalBranch->completion_note = null;
    $historicalBranch->save();

    $secondAssistantRecipient->status = DispositionRecipientStatus::Completed;
    $secondAssistantRecipient->completed_at = now();
    $secondAssistantRecipient->completed_by_user_id = $secondAssistant['user']->getKey();
    $secondAssistantRecipient->completed_by_position_assignment_id = $secondAssistant['assignment']->getKey();
    $secondAssistantRecipient->completion_note = null;
    $secondAssistantRecipient->save();

    $this->actingAs($sectionHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $firstBranch), [
            'completion_note' => 'Tindak lanjut cabang Asisten pertama sudah diselesaikan.',
            'technical_document' => UploadedFile::fake()->createWithContent(
                'bahan-asisten-satu.pdf',
                "%PDF-1.4\nBahan teknis cabang Asisten pertama.\n%%EOF",
            ),
            'technical_document_note' => 'Bahan teknis khusus cabang Asisten pertama.',
        ])
        ->assertRedirect();
    $this->actingAs($sectionHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $historicalBranch), [
            'completion_note' => 'Tindak lanjut cabang Asisten kedua sudah diselesaikan.',
            'technical_document' => UploadedFile::fake()->createWithContent(
                'bahan-asisten-dua.pdf',
                "%PDF-1.4\nBahan teknis cabang Asisten kedua yang berbeda.\n%%EOF",
            ),
            'technical_document_note' => 'Bahan teknis khusus cabang Asisten kedua.',
        ])
        ->assertRedirect();

    $documents = LetterResponseDocument::query()
        ->where('owner_position_id', $sectionHead['position']->getKey())
        ->orderBy('source_recipient_id')
        ->get();

    expect($documents)->toHaveCount(2)
        ->and($documents->pluck('source_recipient_id')->all())->toEqualCanonicalizing([
            $firstBranch->getKey(),
            $historicalBranch->getKey(),
        ])
        ->and(LetterResponseDocumentVersion::query()->count())->toBe(2)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Completed);
});

test('multiple-recipient forwarding rejects invalid targets and labels without partial state changes', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Administrasi Umum',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Aktif');
    $selfHeldSectionHead = m6Position(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian rangkap Asisten');
    m6Assignment($assistant['user'], $selfHeldSectionHead);
    $vacantSectionHead = m6Position(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian kosong');
    $inactiveHolderSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian nonaktif');
    $ambiguousSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian dengan dua penugasan');
    $inactivePositionSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian nonaktif');
    $orphanSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian tanpa unit');
    $wrongParentSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian lintas unit');
    $inactiveUnitSectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian dengan unit nonaktif');
    m6PlacePositionInUnit($assistant['position'], 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($sectionHead['position'], 'M6-BAGIAN-AKTIF', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($selfHeldSectionHead, 'M6-BAGIAN-RANGKAP', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($vacantSectionHead, 'M6-BAGIAN-KOSONG', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($inactiveHolderSectionHead['position'], 'M6-BAGIAN-NONAKTIF', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($ambiguousSectionHead['position'], 'M6-BAGIAN-AMBIGU', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($inactivePositionSectionHead['position'], 'M6-BAGIAN-POSISI-NONAKTIF', 'M6-ASISTEN-ADMINISTRASI');
    m6PlacePositionInUnit($wrongParentSectionHead['position'], 'M6-BAGIAN-LINTAS-UNIT', 'M6-ASISTEN-LAIN');
    m6PlacePositionInUnit($inactiveUnitSectionHead['position'], 'M6-BAGIAN-UNIT-NONAKTIF', 'M6-ASISTEN-ADMINISTRASI');
    m6Assignment(User::factory()->internal()->create(), $ambiguousSectionHead['position']);
    $inactivePositionSectionHead['position']->is_active = false;
    $inactivePositionSectionHead['position']->save();
    $inactiveUnit = m6Unit('M6-BAGIAN-UNIT-NONAKTIF');
    $inactiveUnit->is_active = false;
    $inactiveUnit->save();
    DB::table('users')
        ->where('id', $inactiveHolderSectionHead['user']->getKey())
        ->update(['is_active' => false]);
    $fixture = m6RoutedLetter($executive);
    $activeLabel = InstructionLabel::query()->firstOrFail();
    $inactiveLabel = InstructionLabel::query()
        ->where('id', '!=', $activeLabel->getKey())
        ->firstOrFail();
    $inactiveLabel->is_active = false;
    $inactiveLabel->save();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$activeLabel->getKey()],
        null,
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();

    foreach ([
        [$selfHeldSectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$vacantSectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$inactiveHolderSectionHead['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$ambiguousSectionHead['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$sectionHead['position']->getKey(), [$inactiveLabel->getKey()], 'instruction_label_ids'],
    ] as [$positionId, $labelIds, $errorKey]) {
        $this->actingAs($assistant['user'])
            ->from(route('back-office.dispositions.inbox.show', $assistantRecipient))
            ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
                'recipient_position_ids' => [$positionId],
                'instruction_label_ids' => $labelIds,
                'instruction_note' => '',
            ])
            ->assertSessionHasErrors($errorKey);
    }

    foreach ([
        $executive['position']->getKey(),
        $assistant['position']->getKey(),
        $inactivePositionSectionHead['position']->getKey(),
        $orphanSectionHead['position']->getKey(),
        $wrongParentSectionHead['position']->getKey(),
        $inactiveUnitSectionHead['position']->getKey(),
    ] as $outOfScopePositionId) {
        $this->actingAs($assistant['user'])
            ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
                'recipient_position_ids' => [$outOfScopePositionId],
                'instruction_label_ids' => [$activeLabel->getKey()],
                'instruction_note' => '',
            ])
            ->assertNotFound();
    }

    $this->actingAs($assistant['user'])
        ->from(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
            'recipient_position_ids' => [
                $sectionHead['position']->getKey(),
                $sectionHead['position']->getKey(),
            ],
            'instruction_label_ids' => [$activeLabel->getKey()],
            'instruction_note' => '',
        ])
        ->assertSessionHasErrors('recipient_position_ids.1');

    expect(Disposition::query()->count())->toBe(1)
        ->and(DispositionRecipient::query()->count())->toBe(1)
        ->and($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

test('an assistant without an active organizational unit cannot inspect or forward a branch', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten tanpa unit',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian tujuan');
    $fixture = m6RoutedLetter($executive, 'Validasi unit sumber Asisten');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();

    $this->actingAs($assistant['user'])
        ->get(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->assertNotFound();

    $this->actingAs($assistant['user'])
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
            'recipient_position_ids' => [$sectionHead['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => '',
        ])
        ->assertNotFound();

    expect(Disposition::query()->where('parent_recipient_id', $assistantRecipient->getKey())->exists())
        ->toBeFalse()
        ->and($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

test('stale assistant assignment context returns an Inertia-compatible conflict response', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Administrasi',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Umum');
    m6PlacePositionInUnit($assistant['position'], 'M6-ASISTEN-STALE');
    m6PlacePositionInUnit($sectionHead['position'], 'M6-BAGIAN-STALE', 'M6-ASISTEN-STALE');

    $fixture = m6RoutedLetter($executive, 'Konflik konteks penugasan Asisten');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();
    m6Assignment($assistant['user'], $assistant['position']);

    $this->actingAs($assistant['user'])
        ->from(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->withHeader('X-Inertia', 'true')
        ->post(route('back-office.dispositions.inbox.forward.store', $assistantRecipient), [
            'recipient_position_ids' => [$sectionHead['position']->getKey()],
            'instruction_label_ids' => [$label->getKey()],
            'instruction_note' => '',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $assistantRecipient))
        ->assertSessionHasErrors('workflow');

    expect(Disposition::query()->where('parent_recipient_id', $assistantRecipient->getKey())->exists())
        ->toBeFalse()
        ->and($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

test('multiple-recipient forwarding rolls back recipients and source completion when audit writing fails', function (): void {
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Wali Kota',
        [PermissionName::CreateDispositions],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Kesejahteraan Rakyat',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $sectionHead = m6Actor(OrganizationCatalog::SECTION_HEAD_LEVEL, 'Kepala Bagian Kesejahteraan Rakyat');
    m6PlacePositionInUnit($assistant['position'], 'M6-ASISTEN-KESRA');
    m6PlacePositionInUnit($sectionHead['position'], 'M6-BAGIAN-KESRA', 'M6-ASISTEN-KESRA');
    $fixture = m6RoutedLetter($executive);
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        null,
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new RuntimeException('Simulated forwarding audit failure.'));
    });

    expect(fn () => app(ForwardDisposition::class)->execute(
        $assistant['user'],
        $assistantRecipient,
        [$sectionHead['position']->getKey()],
        [$label->getKey()],
        null,
    ))->toThrow(RuntimeException::class, 'Simulated forwarding audit failure.');

    expect(Disposition::query()->count())->toBe(1)
        ->and(DispositionRecipient::query()->count())->toBe(1)
        ->and($assistantRecipient->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and($assistantRecipient->completed_at)->toBeNull()
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCreated->value)->count())->toBe(1);
});

/**
 * @return array{
 *     executive: array{user: User, position: Position, assignment: PositionAssignment},
 *     assistant: array{user: User, position: Position, assignment: PositionAssignment},
 *     heads: list<array{user: User, position: Position, assignment: PositionAssignment}>,
 *     letter: IncomingLetter,
 *     route: LetterRoute,
 *     assistant_recipient: DispositionRecipient,
 *     branches: list<DispositionRecipient>
 * }
 */
function m6IndependentBranchFixture(): array
{
    $executive = m6Actor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        'Sekretaris Daerah Pemantau',
        [
            PermissionName::ViewExecutiveInbox,
            PermissionName::CreateDispositions,
            PermissionName::ViewLetterActivities,
        ],
    );
    $assistant = m6Actor(
        OrganizationCatalog::ASSISTANT_LEVEL,
        'Asisten Koordinator Cabang',
        [PermissionName::ViewDispositions, PermissionName::CreateDispositions],
    );
    $heads = [
        m6Actor(
            OrganizationCatalog::SECTION_HEAD_LEVEL,
            'Kepala Bagian Cabang Satu',
            [
                PermissionName::ViewDispositions,
                PermissionName::ProcessDispositions,
                PermissionName::ViewLetterActivities,
            ],
        ),
        m6Actor(
            OrganizationCatalog::SECTION_HEAD_LEVEL,
            'Kepala Bagian Cabang Dua',
            [PermissionName::ViewDispositions, PermissionName::ProcessDispositions],
        ),
    ];
    m6PlacePositionInUnit($assistant['position'], 'M6-ASISTEN-CABANG');
    m6PlacePositionInUnit($heads[0]['position'], 'M6-BAGIAN-CABANG-SATU', 'M6-ASISTEN-CABANG');
    m6PlacePositionInUnit($heads[1]['position'], 'M6-BAGIAN-CABANG-DUA', 'M6-ASISTEN-CABANG');
    $fixture = m6RoutedLetter($executive, 'Penanganan cabang independen');
    $label = InstructionLabel::query()->firstOrFail();
    $initialDisposition = app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $fixture['route'],
        [$assistant['position']->getKey()],
        [$label->getKey()],
        'Koordinasikan kepada unit yang sesuai.',
    );
    $assistantRecipient = $initialDisposition->recipients()->firstOrFail();
    $childDisposition = app(ForwardDisposition::class)->execute(
        $assistant['user'],
        $assistantRecipient,
        array_map(
            static fn (array $head): int => (int) $head['position']->getKey(),
            $heads,
        ),
        [$label->getKey()],
        'Tangani secara paralel dan laporkan hasil akhir.',
    );
    $branches = $childDisposition->recipients()
        ->orderBy('recipient_position_id')
        ->get()
        ->mapWithKeys(fn (DispositionRecipient $recipient): array => [
            $recipient->recipient_position_id => $recipient,
        ]);

    return [
        'executive' => $executive,
        'assistant' => $assistant,
        'heads' => $heads,
        'letter' => $fixture['letter'],
        'route' => $fixture['route'],
        'assistant_recipient' => $assistantRecipient,
        'branches' => array_values(array_map(
            fn (array $head): DispositionRecipient => $branches
                ->get($head['position']->getKey()),
            $heads,
        )),
    ];
}

test('section heads process independent branches while assistant and executive receive scoped monitoring', function (): void {
    $fixture = m6IndependentBranchFixture();
    [$firstBranch, $secondBranch] = $fixture['branches'];
    [$firstHead, $secondHead] = $fixture['heads'];

    $this->actingAs($firstHead['user'])
        ->get(route('back-office.dispositions.inbox.show', $firstBranch))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_process_dispositions', true)
            ->where('branch.status', DispositionRecipientStatus::Pending->value)
            ->where('capabilities.can_start_branch', true)
            ->where('capabilities.can_add_follow_up', false)
            ->where('capabilities.can_complete_branch', true)
            ->where('routes.start', route('back-office.dispositions.inbox.branch.start', $firstBranch))
            ->where('routes.complete', route('back-office.dispositions.inbox.branch.complete', $firstBranch))
            ->missing('branch.completed_by.email')
            ->missing('branch.completed_by.assignment_id'));

    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.start', $firstBranch))
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    expect($firstBranch->refresh()->status)->toBe(DispositionRecipientStatus::InProgress)
        ->and($firstBranch->started_at)->not->toBeNull()
        ->and($secondBranch->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress);

    $followUpNote = 'Koordinasi lintas unit telah dilakukan dan dokumen pendukung sedang diperiksa.';
    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.follow-ups.store', $firstBranch), [
            'note' => '  '.$followUpNote.'  ',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    $followUp = DispositionFollowUp::query()->firstOrFail();
    expect($followUp->note)->toBe($followUpNote)
        ->and($followUp->created_by_user_id)->toBe($firstHead['user']->getKey())
        ->and($followUp->created_by_position_assignment_id)->toBe($firstHead['assignment']->getKey());

    $this->actingAs($firstHead['user'])
        ->get(route('back-office.letter-activities.index', [
            'action' => AuditAction::FollowUpAdded->value,
        ]))
        ->assertOk()
        ->assertDontSee($followUpNote, false)
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.action', AuditAction::FollowUpAdded->value)
            ->where('activities.data.0.actor.name', 'Pengguna internal')
            ->where('filterOptions.actions', fn ($actions): bool => collect($actions)
                ->contains(fn (array $option): bool => $option === [
                    'value' => AuditAction::FollowUpAdded->value,
                    'label' => 'Catatan tindak lanjut ditambahkan',
                ]))
            ->missing('activities.data.0.after.note'));

    $firstCompletionNote = 'Cabang pertama selesai dengan rekomendasi yang telah disampaikan.';
    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $firstBranch), [
            'completion_note' => $firstCompletionNote,
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    expect($firstBranch->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($secondBranch->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and(AuditLog::query()->where('action', AuditAction::LetterCompleted->value)->count())->toBe(0);

    $this->actingAs($firstHead['user'])
        ->get(route('back-office.dispositions.inbox.show', $firstBranch))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('branch.status', DispositionRecipientStatus::Completed->value)
            ->where('branch.completion_note', $firstCompletionNote)
            ->where('branch.completed_by.name', $firstHead['user']->name)
            ->where('branch.completed_by.position', $firstHead['position']->name)
            ->where('capabilities.can_start_branch', false)
            ->where('capabilities.can_add_follow_up', false)
            ->where('capabilities.can_complete_branch', false)
            ->missing('branch.completed_by.email')
            ->missing('branch.completed_by.assignment_id')
            ->missing('routes.start')
            ->missing('routes.follow_up')
            ->missing('routes.complete'));

    $this->actingAs($firstHead['user'])
        ->get(route('back-office.letter-activities.index', [
            'action' => AuditAction::DispositionCompleted->value,
        ]))
        ->assertOk()
        ->assertDontSee($firstCompletionNote, false)
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.action', AuditAction::DispositionCompleted->value)
            ->missing('activities.data.0.after.completion_note'));

    $this->actingAs($fixture['assistant']['user'])
        ->get(route('back-office.dispositions.inbox.show', $fixture['assistant_recipient']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('branch', null)
            ->where('branchMonitor.total', 2)
            ->where('branchMonitor.pending', 1)
            ->where('branchMonitor.completed', 1)
            ->has('branchMonitor.branches.0.follow_ups')
            ->missing('branchMonitor.branches.0.completed_by.email')
            ->missing('routes.start')
            ->missing('routes.follow_up')
            ->missing('routes.complete'));

    $this->actingAs($fixture['executive']['user'])
        ->get(route('back-office.executive.inbox.show', $fixture['route']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('branchProgress.phase', 'IN_PROGRESS')
            ->where('branchProgress.total', 2)
            ->where('branchProgress.completed', 1)
            ->missing('branchProgress.branches')
            ->missing('branchProgress.recipient_position'));

    $this->actingAs($secondHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $secondBranch), [
            'completion_note' => 'Cabang kedua diselesaikan langsung setelah verifikasi lapangan tuntas.',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $secondBranch));

    expect($secondBranch->refresh()->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($secondBranch->started_at)->toBeNull()
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Completed)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionStarted->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::FollowUpAdded->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCompleted->value)->count())->toBe(2)
        ->and(AuditLog::query()->where('action', AuditAction::LetterCompleted->value)->count())->toBe(1);

    $this->actingAs($fixture['executive']['user'])
        ->get(route('back-office.executive.inbox.index', ['progress' => 'COMPLETED']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inbox.data', 1)
            ->where('inbox.data.0.branch_progress.phase', 'COMPLETED')
            ->where('summary.completed', 1)
            ->missing('inbox.data.0.branch_progress.branches'));
});

test('branch mutation authorization preserves permission and position boundaries', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $owner = $fixture['heads'][0];
    $otherHead = $fixture['heads'][1];

    $owner['user']->syncRoles([]);
    m6Grant($owner['user'], PermissionName::ViewDispositions);
    $this->actingAs($owner['user'])
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertForbidden();
    $this->actingAs($owner['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Cabang ini tidak boleh diselesaikan tanpa permission proses.',
        ])
        ->assertForbidden();

    $this->actingAs($otherHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertNotFound();
    $this->actingAs($otherHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Kepala Bagian lain tidak boleh menyelesaikan cabang ini.',
        ])
        ->assertNotFound();

    foreach ([$fixture['assistant'], $fixture['executive']] as $nonTerminalActor) {
        $nonTerminalActor['user']->syncRoles([]);
        m6Grant(
            $nonTerminalActor['user'],
            PermissionName::ViewDispositions,
            PermissionName::ProcessDispositions,
        );
        $this->actingAs($nonTerminalActor['user'])
            ->post(route('back-office.dispositions.inbox.branch.start', $branch))
            ->assertNotFound();
        $this->actingAs($nonTerminalActor['user'])
            ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
                'completion_note' => 'Pejabat nonterminal tidak boleh menyelesaikan cabang Kepala Bagian.',
            ])
            ->assertNotFound();
    }

    $technicalAdministrator = User::factory()->internal()->create();
    m6Grant($technicalAdministrator, ...PermissionName::cases());
    $this->actingAs($technicalAdministrator)
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertNotFound();
    $this->actingAs($technicalAdministrator)
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Administrator teknis tanpa Position bisnis harus tetap ditolak.',
        ])
        ->assertNotFound();

    expect($branch->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionStarted->value)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCompleted->value)->exists())->toBeFalse();
});

test('a replacement position holder completes the existing branch with the new historical assignment', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $originalHead = $fixture['heads'][0];
    $recipientPositionId = (int) $originalHead['position']->getKey();

    $originalHead['assignment']->ended_at = now()->subMinute();
    $originalHead['assignment']->save();

    $replacement = User::factory()->internal()->create();
    m6Grant(
        $replacement,
        PermissionName::ViewDispositions,
        PermissionName::ProcessDispositions,
    );
    $replacementAssignment = new PositionAssignment;
    $replacementAssignment->user_id = $replacement->getKey();
    $replacementAssignment->position_id = $recipientPositionId;
    $replacementAssignment->started_at = now()->subSeconds(30);
    $replacementAssignment->ended_at = null;
    $replacementAssignment->assigned_by_user_id = null;
    $replacementAssignment->save();

    $this->actingAs($originalHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Pejabat lama tidak lagi memiliki assignment aktif untuk cabang ini.',
        ])
        ->assertNotFound();

    $completionNote = 'Penyelesaian dilanjutkan pejabat pengganti berdasarkan histori pekerjaan yang tersedia.';
    $this->actingAs($replacement)
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => '  '.$completionNote.'  ',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $branch));

    expect($branch->refresh()->recipient_position_id)->toBe($recipientPositionId)
        ->and($branch->status)->toBe(DispositionRecipientStatus::Completed)
        ->and($branch->completion_note)->toBe($completionNote)
        ->and($branch->completed_by_user_id)->toBe($replacement->getKey())
        ->and($branch->completed_by_position_assignment_id)->toBe($replacementAssignment->getKey());

    $this->actingAs($replacement)
        ->get(route('back-office.dispositions.inbox.show', $branch))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('branch.completed_by.name', $replacement->name)
            ->where('branch.completed_by.position', $originalHead['position']->name)
            ->missing('branch.completed_by.email')
            ->missing('branch.completed_by.assignment_id'));
});

test('completed branch presentation fails closed for a mismatched historical position assignment', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $owner = $fixture['heads'][0];
    $otherHead = $fixture['heads'][1];

    $this->actingAs($owner['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Cabang diselesaikan sebelum simulasi kerusakan metadata historis.',
        ])
        ->assertRedirect();

    DB::table('disposition_recipients')
        ->where('id', $branch->getKey())
        ->update([
            'completed_by_user_id' => $otherHead['user']->getKey(),
            'completed_by_position_assignment_id' => $otherHead['assignment']->getKey(),
        ]);

    $this->actingAs($owner['user'])
        ->getJson(route('back-office.dispositions.inbox.show', $branch))
        ->assertConflict();
});

test('branch lifecycle rejects invalid input stale states and mutations after completion', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $head = $fixture['heads'][0];

    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.follow-ups.store', $branch), [
            'note' => 'Catatan ini cukup panjang tetapi cabang belum dimulai.',
        ])
        ->assertConflict();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Pendek',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('completion_note');
    $this->actingAs($head['user'])
        ->withHeader('X-Inertia', 'true')
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Pendek',
        ])
        ->assertRedirect(route('back-office.dispositions.inbox.show', $branch))
        ->assertSessionHasErrors('completion_note');
    $this->flushHeaders();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => str_repeat('a', 2001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('completion_note');

    $this->actingAs($head['user'])
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertRedirect();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertConflict();
    $this->actingAs($head['user'])
        ->from(route('back-office.dispositions.inbox.show', $branch))
        ->withHeader('X-Inertia', 'true')
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertRedirect(route('back-office.dispositions.inbox.show', $branch))
        ->assertSessionHasErrors('workflow');
    $this->flushHeaders();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.follow-ups.store', $branch), [
            'note' => 'Terlalu',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('note');
    $this->actingAs($head['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Penyelesaian cabang telah diverifikasi dan dinyatakan final.',
        ])
        ->assertRedirect();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.follow-ups.store', $branch), [
            'note' => 'Catatan tambahan tidak boleh masuk setelah cabang final.',
        ])
        ->assertConflict();
    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Cabang yang final tidak boleh diselesaikan untuk kedua kalinya.',
        ])
        ->assertConflict();

    DB::table('incoming_letters')
        ->where('id', $fixture['letter']->getKey())
        ->update(['status' => IncomingLetterStatus::Routed->value]);
    $secondBranch = $fixture['branches'][1];
    $secondHead = $fixture['heads'][1];
    $this->actingAs($secondHead['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.start', $secondBranch))
        ->assertConflict();

    expect(DispositionFollowUp::query()->count())->toBe(0);
});

test('follow-ups are append-only and audit failure rolls branch changes back atomically', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $head = $fixture['heads'][0];

    $this->actingAs($head['user'])
        ->post(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertRedirect();
    $this->actingAs($head['user'])
        ->post(route('back-office.dispositions.inbox.branch.follow-ups.store', $branch), [
            'note' => 'Catatan awal yang bersifat permanen untuk histori cabang.',
        ])
        ->assertRedirect();
    $followUp = DispositionFollowUp::query()->firstOrFail();

    expect(fn () => tap($followUp, function (DispositionFollowUp $record): void {
        $record->note = 'Catatan ini tidak boleh menggantikan histori yang sudah ada.';
        $record->save();
    }))->toThrow(LogicException::class)
        ->and(fn () => $followUp->delete())->toThrow(LogicException::class);

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new RuntimeException('Simulated branch completion audit failure.'));
    });

    expect(fn () => app(CompleteDispositionBranch::class)->execute(
        $head['user'],
        $branch,
        'Penyelesaian ini harus dibatalkan ketika audit gagal ditulis.',
    ))->toThrow(RuntimeException::class, 'Simulated branch completion audit failure.');

    expect($branch->refresh()->status)->toBe(DispositionRecipientStatus::InProgress)
        ->and($branch->completed_at)->toBeNull()
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCompleted->value)->exists())->toBeFalse();
});

test('failure while auditing aggregate letter completion rolls back the final branch and its first audit', function (): void {
    $fixture = m6IndependentBranchFixture();
    [$firstBranch, $finalBranch] = $fixture['branches'];
    [$firstHead, $finalHead] = $fixture['heads'];

    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $firstBranch), [
            'completion_note' => 'Cabang pertama telah selesai sebelum pengujian transaksi cabang terakhir.',
        ])
        ->assertRedirect();

    $realRecordAudit = app(RecordAudit::class);
    $auditCall = 0;
    $this->mock(RecordAudit::class, function (MockInterface $mock) use ($realRecordAudit, &$auditCall): void {
        $mock->shouldReceive('execute')
            ->twice()
            ->andReturnUsing(function (...$arguments) use ($realRecordAudit, &$auditCall): AuditLog {
                $auditCall++;

                if ($auditCall === 2) {
                    throw new RuntimeException('Simulated aggregate completion audit failure.');
                }

                return $realRecordAudit->execute(...$arguments);
            });
    });

    expect(fn () => app(CompleteDispositionBranch::class)->execute(
        $finalHead['user'],
        $finalBranch,
        'Cabang terakhir harus rollback ketika audit penyelesaian surat gagal.',
    ))->toThrow(RuntimeException::class, 'Simulated aggregate completion audit failure.');

    expect($finalBranch->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and($finalBranch->completed_at)->toBeNull()
        ->and($finalBranch->completed_by_user_id)->toBeNull()
        ->and($finalBranch->completed_by_position_assignment_id)->toBeNull()
        ->and($finalBranch->completion_note)->toBeNull()
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::InProgress)
        ->and(AuditLog::query()->where('action', AuditAction::DispositionCompleted->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::LetterCompleted->value)->count())->toBe(0);
});

test('branch mutation limiter is shared across lifecycle endpoints', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $head = $fixture['heads'][0];

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->actingAs($head['user'])
            ->postJson(route('back-office.dispositions.inbox.branch.follow-ups.store', $branch), [
                'note' => 'Pendek',
            ])
            ->assertUnprocessable();
    }

    $this->actingAs($head['user'])
        ->postJson(route('back-office.dispositions.inbox.branch.start', $branch))
        ->assertTooManyRequests();

    expect($branch->refresh()->status)->toBe(DispositionRecipientStatus::Pending)
        ->and(DispositionFollowUp::query()->count())->toBe(0);
});

test('executive inbox fails closed for inconsistent route and branch graphs', function (): void {
    $fixture = m6IndependentBranchFixture();

    DB::table('letter_routes')
        ->where('id', $fixture['route']->getKey())
        ->update(['status' => LetterRouteStatus::Pending->value]);

    $this->actingAs($fixture['executive']['user'])
        ->get(route('back-office.executive.inbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inbox.data', 0)
            ->where('summary.pending', 0)
            ->where('summary.in_progress', 0));

    DB::table('letter_routes')
        ->where('id', $fixture['route']->getKey())
        ->update(['status' => LetterRouteStatus::Completed->value]);
    DB::table('disposition_recipients')
        ->where('id', $fixture['branches'][0]->getKey())
        ->update(['recipient_position_id' => $fixture['assistant']['position']->getKey()]);

    $this->actingAs($fixture['executive']['user'])
        ->getJson(route('back-office.executive.inbox.show', $fixture['route']))
        ->assertConflict();

    $this->actingAs($fixture['executive']['user'])
        ->getJson(route('back-office.executive.inbox.index', ['progress' => 'UNKNOWN']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('progress');
});

test('response dossier supports immutable materials proposal revision mandate and finalization', function (): void {
    $fixture = m6IndependentBranchFixture();
    [$firstBranch, $secondBranch] = $fixture['branches'];
    [$firstHead, $secondHead] = $fixture['heads'];

    m6Grant($firstHead['user'], PermissionName::ViewLetterResponses, PermissionName::ContributeLetterResponses);
    m6Grant($secondHead['user'], PermissionName::ViewLetterResponses, PermissionName::ContributeLetterResponses);
    m6Grant(
        $fixture['assistant']['user'],
        PermissionName::ViewLetterResponses,
        PermissionName::ContributeLetterResponses,
        PermissionName::ReviewLetterResponses,
    );
    m6Grant(
        $fixture['executive']['user'],
        PermissionName::ViewLetterResponses,
        PermissionName::ContributeLetterResponses,
        PermissionName::ReviewLetterResponses,
        PermissionName::AuthorizeLetterResponses,
    );

    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $firstBranch), [
            'completion_note' => 'Cabang pertama selesai dengan bahan telaah teknis terlampir.',
            'technical_document' => UploadedFile::fake()->create('bahan-teknis.pdf', 64, 'application/pdf'),
            'technical_document_note' => 'Telaah teknis final dari Kepala Bagian terkait.',
        ])
        ->assertRedirect();

    $dossier = LetterResponseDossier::query()->firstOrFail();
    $technicalVersion = LetterResponseDocumentVersion::query()->firstOrFail();

    expect($dossier->incoming_letter_id)->toBe($fixture['letter']->getKey())
        ->and($technicalVersion->version_number)->toBe(1)
        ->and($technicalVersion->storage_disk)->toBe('letter-response-documents')
        ->and($technicalVersion->storage_path)->toStartWith('letters/'.$fixture['letter']->getKey().'/')
        ->and(Storage::disk('letter-response-documents')->exists($technicalVersion->storage_path))->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::LetterResponseDossierOpened->value)->count())->toBe(1);

    $this->actingAs($secondHead['user'])
        ->get(route('back-office.letter-responses.documents.preview', [$dossier, $technicalVersion]))
        ->assertNotFound();

    $this->actingAs($secondHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $secondBranch), [
            'completion_note' => 'Cabang kedua selesai dan hasil akhirnya siap dikonsolidasikan.',
        ])
        ->assertRedirect();

    expect(LetterResponseDossier::query()->count())->toBe(1)
        ->and($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Completed);

    $this->actingAs($fixture['assistant']['user'])
        ->post(route('back-office.letter-responses.proposals.store', [$dossier, $fixture['assistant_recipient']]), [
            'document' => UploadedFile::fake()->create('proposal-asisten.pdf', 64, 'application/pdf'),
            'revision_note' => 'Proposal awal berdasarkan seluruh bahan Kepala Bagian.',
        ])
        ->assertRedirect(route('back-office.letter-responses.show', $dossier));

    $proposal = LetterResponseDocumentVersion::query()->orderByDesc('id')->firstOrFail();

    expect(DB::table('letter_response_document_sources')
        ->where('target_version_id', $proposal->getKey())
        ->where('source_version_id', $technicalVersion->getKey())
        ->exists())->toBeTrue();

    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.documents.return', [$dossier, $proposal->document]), [
            'reason' => 'Gabungkan rekomendasi cabang kedua dan perjelas dasar tindak lanjut.',
        ])
        ->assertRedirect(route('back-office.letter-responses.show', $dossier));

    expect(LetterResponseReview::query()->count())->toBe(1);

    $this->actingAs($fixture['assistant']['user'])
        ->post(route('back-office.letter-responses.documents.versions.store', [$dossier, $proposal->document]), [
            'document' => UploadedFile::fake()->createWithContent(
                'proposal-asisten-revisi.pdf',
                '%PDF-1.4 proposal revision with additional substance',
            ),
            'revision_note' => 'Revisi menggabungkan seluruh rekomendasi teknis yang diminta.',
        ])
        ->assertRedirect(route('back-office.letter-responses.show', $dossier));

    $revisedProposal = LetterResponseDocumentVersion::query()
        ->where('letter_response_document_id', $proposal->letter_response_document_id)
        ->orderByDesc('version_number')
        ->firstOrFail();

    expect($revisedProposal->version_number)->toBe(2)
        ->and($revisedProposal->replaces_version_id)->toBe($proposal->getKey())
        ->and(DB::table('letter_response_document_sources')
            ->where('target_version_id', $revisedProposal->getKey())
            ->where('source_version_id', $technicalVersion->getKey())
            ->exists())->toBeTrue();

    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.mandates.store', $dossier), [
            'source_version_public_id' => $revisedProposal->public_id,
            'signatory_position_code' => $fixture['executive']['position']->code,
            'subject' => 'Balasan resmi hasil tindak lanjut surat masuk',
        ])
        ->assertRedirect(route('back-office.letter-responses.show', $dossier));

    $mandate = OutgoingLetter::query()->firstOrFail();
    expect($mandate->source_document_version_id)->toBe($revisedProposal->getKey())
        ->and($mandate->signatory_position_id)->toBe($fixture['executive']['position']->getKey());

    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.finalize', $dossier))
        ->assertRedirect(route('back-office.letter-responses.show', $dossier));

    expect($dossier->refresh()->status->value)->toBe('FINALIZED')
        ->and(AuditLog::query()->where('action', AuditAction::LetterResponseDocumentReturned->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::LetterResponseMandateAuthorized->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::LetterResponseDossierFinalized->value)->count())->toBe(1);

    $this->actingAs($fixture['executive']['user'])
        ->get(route('back-office.letter-responses.show', $dossier))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_letter_responses', true)
            ->where('auth.capabilities.can_contribute_letter_responses', true)
            ->where('auth.capabilities.can_review_letter_responses', true)
            ->where('auth.capabilities.can_authorize_letter_responses', true)
            ->where('dossier.status', 'FINALIZED')
            ->has('dossier.mandates', 1)
            ->missing('dossier.assistants.0.children.0.material.current_version.storage_disk')
            ->missing('dossier.assistants.0.children.0.material.current_version.storage_path')
            ->missing('dossier.assistants.0.children.0.material.current_version.uploaded_by.email'));
});

test('response dossier distinguishes permission denial from position and resource boundaries', function (): void {
    $fixture = m6IndependentBranchFixture();
    $branch = $fixture['branches'][0];
    $owner = $fixture['heads'][0];

    $this->actingAs($owner['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $branch), [
            'completion_note' => 'Penyelesaian membuka dossier untuk pengujian batas akses.',
        ])
        ->assertRedirect();
    $dossier = LetterResponseDossier::query()->firstOrFail();

    $this->actingAs($owner['user'])
        ->get(route('back-office.letter-responses.index'))
        ->assertForbidden();

    $unrelated = m6Actor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Tidak Terkait',
        [PermissionName::ViewLetterResponses],
    );
    $this->actingAs($unrelated['user'])
        ->get(route('back-office.letter-responses.show', $dossier))
        ->assertNotFound();

    $technicalAdministrator = User::factory()->internal()->withTwoFactor()->create();
    m6Grant($technicalAdministrator, ...PermissionName::cases());
    $this->actingAs($technicalAdministrator)
        ->get(route('back-office.letter-responses.index'))
        ->assertNotFound();
});

test('outgoing letter publication runs from numbering through secure public delivery', function (): void {
    Notification::fake();
    $fixture = m6IndependentBranchFixture();
    [$firstBranch, $secondBranch] = $fixture['branches'];
    [$firstHead, $secondHead] = $fixture['heads'];
    m6Grant($firstHead['user'], PermissionName::ViewLetterResponses, PermissionName::ContributeLetterResponses);
    m6Grant($secondHead['user'], PermissionName::ViewLetterResponses, PermissionName::ContributeLetterResponses);
    m6Grant(
        $fixture['assistant']['user'],
        PermissionName::ViewLetterResponses,
        PermissionName::ContributeLetterResponses,
        PermissionName::ViewOutgoingRegister,
    );
    m6Grant(
        $fixture['executive']['user'],
        PermissionName::ViewLetterResponses,
        PermissionName::ContributeLetterResponses,
        PermissionName::AuthorizeLetterResponses,
        PermissionName::ViewOutgoingRegister,
    );

    $this->actingAs($firstHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $firstBranch), [
            'completion_note' => 'Cabang teknis pertama telah selesai dan menyertakan bahan balasan.',
            'technical_document' => UploadedFile::fake()->create('bahan-teknis.pdf', 32, 'application/pdf'),
            'technical_document_note' => 'Bahan teknis untuk penyusunan proposal balasan resmi.',
        ])
        ->assertRedirect();
    $this->actingAs($secondHead['user'])
        ->post(route('back-office.dispositions.inbox.branch.complete', $secondBranch), [
            'completion_note' => 'Cabang teknis kedua telah selesai dan siap dirangkum oleh Asisten.',
        ])
        ->assertRedirect();

    $dossier = LetterResponseDossier::query()->firstOrFail();
    $this->actingAs($fixture['assistant']['user'])
        ->post(route('back-office.letter-responses.proposals.store', [$dossier, $fixture['assistant_recipient']]), [
            'document' => UploadedFile::fake()->create('proposal-balasan.pdf', 32, 'application/pdf'),
            'revision_note' => 'Proposal final berdasarkan hasil seluruh cabang teknis terkait.',
        ])
        ->assertRedirect();
    $proposal = LetterResponseDocumentVersion::query()->orderByDesc('id')->firstOrFail();
    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.mandates.store', $dossier), [
            'source_version_public_id' => $proposal->public_id,
            'signatory_position_code' => $fixture['executive']['position']->code,
            'subject' => 'Balasan resmi atas permohonan koordinasi program',
        ])
        ->assertRedirect();
    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.mandates.store', $dossier), [
            'source_version_public_id' => $proposal->public_id,
            'signatory_position_code' => $fixture['executive']['position']->code,
            'subject' => 'Tembusan resmi hasil koordinasi program',
        ])
        ->assertRedirect();
    $outgoing = OutgoingLetter::query()->orderBy('id')->firstOrFail();
    $withdrawnMandate = OutgoingLetter::query()->orderByDesc('id')->firstOrFail();
    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.letter-responses.finalize', $dossier))
        ->assertRedirect();

    $officer = User::factory()->internal()->create();
    m6Grant(
        $officer,
        PermissionName::ViewOutgoingRegister,
        PermissionName::NumberOutgoingLetters,
        PermissionName::DeliverOutgoingLetters,
    );
    $officerPosition = m6Position(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        'Petugas Register Surat Keluar',
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );
    m6Assignment($officer, $officerPosition);
    $generalAffairsHead = User::factory()->internal()->create();
    m6Grant(
        $generalAffairsHead,
        PermissionName::ViewOutgoingRegister,
        PermissionName::VerifyOutgoingLetters,
    );
    $generalAffairsHeadPosition = m6Position(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'Kepala Bagian Umum Verifikator',
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );
    m6Assignment($generalAffairsHead, $generalAffairsHeadPosition);

    DB::beginTransaction();
    try {
        $this->actingAs($fixture['executive']['user'])
            ->post(route('back-office.outgoing-letters.withdraw', $withdrawnMandate), [
                'withdrawal_reason' => 'Mandat kedua ditarik untuk menguji batas mandat aktif terakhir.',
            ])
            ->assertRedirect();
        $this->actingAs($fixture['executive']['user'])
            ->postJson(route('back-office.outgoing-letters.withdraw', $outgoing), [
                'withdrawal_reason' => 'Mandat aktif terakhir tidak boleh ikut ditarik setelah finalisasi.',
            ])
            ->assertConflict();
    } finally {
        DB::rollBack();
        $outgoing->refresh();
        $withdrawnMandate->refresh();
    }

    $this->actingAs($officer)
        ->post(route('back-office.outgoing-letters.assign-number', $outgoing), [
            'outgoing_number' => '005/1201/SETDA/2026',
            'letter_date' => '2026-09-01',
        ])
        ->assertRedirect();

    expect($outgoing->refresh()->status->value)->toBe('NUMBER_ASSIGNED')
        ->and($outgoing->agenda_year)->toBe(2026);

    $this->actingAs($officer)
        ->postJson(route('back-office.outgoing-letters.assign-number', $outgoing), [
            'outgoing_number' => '005/9999/SETDA/2026',
            'letter_date' => '2026-09-01',
        ])
        ->assertConflict();
    $this->actingAs($officer)
        ->postJson(route('back-office.outgoing-letters.assign-number', $withdrawnMandate), [
            'outgoing_number' => '005/1201/SETDA/2026',
            'letter_date' => '2026-09-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('outgoing_number');
    $this->actingAs($fixture['executive']['user'])
        ->post(route('back-office.outgoing-letters.withdraw', $withdrawnMandate), [
            'withdrawal_reason' => 'Mandat tembusan tidak lagi diperlukan setelah konsolidasi akhir.',
        ])
        ->assertRedirect();

    expect($withdrawnMandate->refresh()->status->value)->toBe('WITHDRAWN');

    $this->actingAs($fixture['assistant']['user'])
        ->post(route('back-office.outgoing-letters.documents.store', $outgoing), [
            'signed_document' => UploadedFile::fake()->create('balasan-ditandatangani.pdf', 48, 'application/pdf'),
            'upload_note' => 'PDF final sudah bernomor dan ditandatangani di luar sistem.',
        ])
        ->assertRedirect();
    $finalVersion = OutgoingLetterDocumentVersion::query()->firstOrFail();

    expect($outgoing->refresh()->status->value)->toBe('SIGNED_DOCUMENT_UPLOADED')
        ->and($finalVersion->version_number)->toBe(1)
        ->and($finalVersion->storage_path)->toStartWith(
            'letters/'.$fixture['letter']->getKey().'/mandates/'.$outgoing->getKey().'/',
        )
        ->and(Storage::disk('outgoing-letter-documents')->exists($finalVersion->storage_path))->toBeTrue();

    $this->actingAs($generalAffairsHead)
        ->post(route('back-office.outgoing-letters.return-document', $outgoing), [
            'revision_reason' => 'Halaman tanda tangan belum terbaca jelas pada dokumen final.',
        ])
        ->assertRedirect();
    $this->actingAs($generalAffairsHead)
        ->postJson(route('back-office.outgoing-letters.verify', $outgoing))
        ->assertConflict();
    $this->actingAs($fixture['assistant']['user'])
        ->post(route('back-office.outgoing-letters.documents.store', $outgoing), [
            'signed_document' => UploadedFile::fake()->createWithContent(
                'balasan-ditandatangani-revisi.pdf',
                "%PDF-1.4\nDokumen final revisi dengan halaman tanda tangan yang telah diperjelas.\n%%EOF",
            ),
            'upload_note' => 'Versi perbaikan memuat halaman tanda tangan yang terbaca jelas.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $revisedFinalVersion = OutgoingLetterDocumentVersion::query()->orderByDesc('version_number')->firstOrFail();

    expect($revisedFinalVersion->version_number)->toBe(2)
        ->and($revisedFinalVersion->replaces_version_id)->toBe($finalVersion->getKey())
        ->and(OutgoingLetterDocumentReview::query()->count())->toBe(1)
        ->and(Storage::disk('outgoing-letter-documents')->exists($finalVersion->storage_path))->toBeTrue();

    $this->actingAs($generalAffairsHead)
        ->post(route('back-office.outgoing-letters.verify', $outgoing), [
            'verification_note' => 'Nomor, tanggal, penandatangan, dan PDF telah sesuai.',
        ])
        ->assertRedirect();

    expect($outgoing->refresh()->status->value)->toBe('ADMIN_VERIFIED')
        ->and(OutgoingLetterDocumentReview::query()->count())->toBe(2);

    $this->actingAs($officer)
        ->get(route('back-office.outgoing-letters.show', $outgoing))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_outgoing_register', true)
            ->where('auth.capabilities.can_number_outgoing_letters', true)
            ->where('auth.capabilities.can_verify_outgoing_letters', false)
            ->where('auth.capabilities.can_deliver_outgoing_letters', true)
            ->where('outgoingLetter.status', 'ADMIN_VERIFIED')
            ->where('outgoingLetter.capabilities.can_deliver', true)
            ->missing('outgoingLetter.documents.0.storage_disk')
            ->missing('outgoingLetter.documents.0.storage_path')
            ->missing('outgoingLetter.documents.0.uploaded_by_email'));
    $this->actingAs($officer)
        ->get(route('back-office.outgoing-letters.documents.preview', [$outgoing, $revisedFinalVersion]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->actingAs($officer)
        ->get(route('back-office.outgoing-letters.documents.download', [$withdrawnMandate, $revisedFinalVersion]))
        ->assertNotFound();

    $submissionId = $fixture['letter']->submission()->value('id');
    DB::beginTransaction();
    try {
        LetterSubmission::query()->whereKey($submissionId)->update([
            'source' => SubmissionSource::Manual->value,
        ]);
        $this->actingAs($officer)
            ->post(route('back-office.outgoing-letters.deliver', $outgoing))
            ->assertSessionHasErrors(['delivery_method', 'recipient_name', 'delivered_at']);
        $this->actingAs($officer)
            ->post(route('back-office.outgoing-letters.deliver', $outgoing), [
                'delivery_method' => 'IN_PERSON',
                'recipient_name' => 'La Ode Penerima Surat',
                'delivered_at' => '2026-09-01 01:30:00',
                'delivery_note' => 'Diserahkan langsung dan identitas penerima telah diperiksa.',
            ])
            ->assertRedirect();

        $manualDelivery = OutgoingLetterDelivery::query()->firstOrFail();
        expect($outgoing->refresh()->status->value)->toBe('DELIVERED')
            ->and($manualDelivery->method->value)->toBe('IN_PERSON')
            ->and($manualDelivery->recipient_name)->toBe('La Ode Penerima Surat');
    } finally {
        DB::rollBack();
        $outgoing->refresh();
        $dossier->refresh();
    }

    $this->actingAs($officer)
        ->post(route('back-office.outgoing-letters.deliver', $outgoing))
        ->assertRedirect();

    expect($outgoing->refresh()->status->value)->toBe('DELIVERED')
        ->and(OutgoingLetterDelivery::query()->count())->toBe(1)
        ->and($dossier->refresh()->status->value)->toBe('FULFILLED')
        ->and(AuditLog::query()->where('action', AuditAction::OutgoingLetterNumberAssigned->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::OutgoingLetterDocumentVersionCreated->value)->count())->toBe(2)
        ->and(AuditLog::query()->where('action', AuditAction::OutgoingLetterDocumentReturned->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::OutgoingLetterAdminVerified->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::OutgoingLetterDelivered->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::LetterResponseDossierFulfilled->value)->count())->toBe(1);

    $submission = $fixture['letter']->submission()->with('submitter')->firstOrFail();
    Notification::assertSentTo($submission->submitter, OfficialResponseAvailable::class);
    Notification::assertSentTimes(OfficialResponseAvailable::class, 1);
    Notification::assertCount(1);

    $this->actingAs($submission->submitter)
        ->get(route('public.submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('responseTracker.status', 'RESPONSE_AVAILABLE')
            ->has('responseTracker.responses', 1)
            ->where('responseTracker.responses.0.outgoing_number', '005/1201/SETDA/2026')
            ->missing('responseTracker.responses.0.storage_disk')
            ->missing('responseTracker.responses.0.storage_path'));
    $this->actingAs($submission->submitter)
        ->get(route('public.submissions.responses.preview', [$submission, $outgoing]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($submission->submitter)
        ->get(route('public.submissions.responses.download', [$submission, $outgoing]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $otherPublicUser = User::factory()->create();
    $this->actingAs($otherPublicUser)
        ->get(route('public.submissions.responses.preview', [$submission, $outgoing]))
        ->assertNotFound();
    $this->actingAs($otherPublicUser)
        ->get(route('public.submissions.responses.download', [$submission, $outgoing]))
        ->assertNotFound();

    $this->actingAs($officer)
        ->get(route('back-office.outgoing-letters.index', [
            'status' => 'DELIVERED',
            'source' => 'ONLINE',
            'year' => 2026,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('letters.data', 1)
            ->where('letters.data.0.public_id', $outgoing->public_id)
            ->where('summary.total', 2)
            ->where('summary.delivered_this_month', 1)
            ->missing('letters.data.0.storage_disk')
            ->missing('letters.data.0.storage_path'));
});

test('outgoing register rejects missing permissions and technical administrators without a business position', function (): void {
    $fixture = m6IndependentBranchFixture();
    $dossier = new LetterResponseDossier;
    $dossier->incoming_letter_id = $fixture['letter']->getKey();
    $dossier->status = LetterResponseDossierStatus::Open;
    $dossier->opened_at = now();
    $dossier->save();

    $technicalAdministrator = User::factory()->internal()->withTwoFactor()->create();
    m6Grant($technicalAdministrator, ...PermissionName::cases());
    $this->actingAs($technicalAdministrator)
        ->get(route('back-office.outgoing-letters.index'))
        ->assertNotFound();

    $withoutPermission = $fixture['assistant']['user'];
    $this->actingAs($withoutPermission)
        ->get(route('back-office.outgoing-letters.index'))
        ->assertForbidden();

    expect(OutgoingLetterDocumentVersion::query()->count())->toBe(0)
        ->and(OutgoingLetterDelivery::query()->count())->toBe(0);
});

test('disposition branch action endpoints redirect GET requests to the detail page instead of method not allowed', function (): void {
    $fixture = m6IndependentBranchFixture();
    $firstBranch = $fixture['branches'][0];
    $head = $fixture['heads'][0];

    $this->actingAs($head['user'])
        ->get("/back-office/dispositions/inbox/recipients/{$firstBranch->getKey()}/complete")
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    $this->actingAs($head['user'])
        ->get("/back-office/dispositions/inbox/recipients/{$firstBranch->getKey()}/start")
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    $this->actingAs($head['user'])
        ->get("/back-office/dispositions/inbox/recipients/{$firstBranch->getKey()}/follow-ups")
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));

    $this->actingAs($head['user'])
        ->get("/back-office/dispositions/inbox/recipients/{$firstBranch->getKey()}/forward")
        ->assertRedirect(route('back-office.dispositions.inbox.show', $firstBranch));
});
