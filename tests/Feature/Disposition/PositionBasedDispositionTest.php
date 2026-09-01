<?php

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

test('executive creates one atomic first disposition and assistant receives only its own branch', function (): void {
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
            'recipient_position_id' => $assistant['position']->getKey(),
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
        ->and($audit->metadata['recipient_id'])->toBe($recipient->getKey());

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
            ->where('firstDisposition.recipient_position.id', $assistant['position']->getKey())
            ->where('firstDisposition.status', DispositionRecipientStatus::Pending->value)
            ->has('assistantPositions', 0)
            ->missing('firstDisposition.disposed_by.email'));

    $this->actingAs($executive['user'])
        ->postJson(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
            'recipient_position_id' => $assistant['position']->getKey(),
            'instruction_label_ids' => $labels->modelKeys(),
            'instruction_note' => '',
        ])
        ->assertConflict();

    $otherFixture = m6RoutedLetter($executive, 'Surat khusus Asisten Pemerintahan');
    app(CreateInitialDisposition::class)->execute(
        $executive['user'],
        $otherFixture['route'],
        $otherAssistant['position']->getKey(),
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
        'recipient_position_id' => $assistant['position']->getKey(),
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
        [$executive['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_id'],
        [$selfAssistant->getKey(), [$activeLabel->getKey()], 'recipient_position_id'],
        [$sectionHead->getKey(), [$activeLabel->getKey()], 'recipient_position_id'],
        [$vacantAssistant->getKey(), [$activeLabel->getKey()], 'recipient_position_id'],
        [$inactiveAssistant['position']->getKey(), [$activeLabel->getKey()], 'recipient_position_id'],
        [$assistant['position']->getKey(), [$inactiveLabel->getKey()], 'instruction_label_ids'],
    ] as [$positionId, $labelIds, $errorKey]) {
        $this->actingAs($executive['user'])
            ->from(route('back-office.executive.inbox.show', $fixture['route']))
            ->post(route('back-office.executive.inbox.dispositions.store', $fixture['route']), [
                'recipient_position_id' => $positionId,
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
            'recipient_position_id' => $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
                'recipient_position_id',
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
        $assistant['position']->getKey(),
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
