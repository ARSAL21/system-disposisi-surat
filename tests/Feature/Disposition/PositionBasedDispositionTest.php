<?php

use App\Actions\CompleteDispositionBranch;
use App\Actions\CreateInitialDisposition;
use App\Actions\ForwardDisposition;
use App\Actions\RecordAudit;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
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
use App\Models\LetterRoute;
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
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

function m6Unit(string $code): OrganizationalUnit
{
    $existing = OrganizationalUnit::query()->where('code', $code)->first();

    if ($existing instanceof OrganizationalUnit) {
        return $existing;
    }

    $unit = new OrganizationalUnit;
    $unit->code = $code;
    $unit->name = str_replace('_', ' ', $code);
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
        ->and($audit->metadata['recipient_ids'])->toHaveCount(2);

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
        [$executive['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$assistant['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$selfHeldSectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$vacantSectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
        [$inactiveHolderSectionHead['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_ids'],
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
