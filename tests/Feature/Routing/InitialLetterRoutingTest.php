<?php

use App\Actions\RecordAudit;
use App\Actions\RouteIncomingLetter;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\PermissionName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Exceptions\InitialLetterRoutingStateConflict;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Date::setTestNow('2026-08-31 02:00:00');
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
});

afterEach(function (): void {
    Date::setTestNow();
});

function routingLevel(string $code): PositionLevel
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
        'ASSISTANT' => 30,
        OrganizationCatalog::SECTION_HEAD_LEVEL => 40,
        default => 90,
    };
    $level->is_active = true;
    $level->save();

    return $level;
}

function routingUnit(string $code): OrganizationalUnit
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

function routingPosition(
    string $levelCode,
    ?string $unitCode = null,
    ?string $name = null,
): Position {
    $position = new Position;
    $position->position_level_id = routingLevel($levelCode)->getKey();
    $position->organizational_unit_id = $unitCode === null
        ? null
        : routingUnit($unitCode)->getKey();
    $position->code = 'ROUTING-'.Str::upper(Str::random(12));
    $position->name = $name ?? match ($levelCode) {
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL => 'Staf Administrasi Surat',
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL => 'Sekretaris Daerah',
        OrganizationCatalog::SECTION_HEAD_LEVEL => 'Kepala Bagian Umum',
        default => 'Asisten Pemerintahan',
    };
    $position->is_active = true;
    $position->save();

    return $position;
}

function routingAssignment(User $user, Position $position): PositionAssignment
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

function routingGrant(User $user, PermissionName ...$permissions): void
{
    $permissionModels = collect($permissions)->map(
        fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
    );
    $role = Role::findOrCreate('routing-role-'.Str::lower(Str::random(12)));
    $role->syncPermissions($permissionModels);
    $user->assignRole($role);
}

/** @return array{user: User, position: Position, assignment: PositionAssignment} */
function routingActor(
    string $levelCode,
    ?string $unitCode,
    array $permissions,
    ?string $positionName = null,
): array {
    $user = User::factory()->internal()->create();
    routingGrant($user, ...$permissions);
    $position = routingPosition($levelCode, $unitCode, $positionName);
    $assignment = routingAssignment($user, $position);

    return compact('user', 'position', 'assignment');
}

/** @return array{user: User, position: Position, assignment: PositionAssignment} */
function routingExecutive(string $positionName = 'Sekretaris Daerah'): array
{
    return routingActor(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        null,
        [],
        $positionName,
    );
}

/** @return array{letter: IncomingLetter, document: LetterDocument, submission_document: SubmissionDocument} */
function routingLetter(
    string $subject = 'Permohonan rapat koordinasi',
    IncomingLetterStatus $status = IncomingLetterStatus::Registered,
    ?DateTimeInterface $receivedAt = null,
    string $contents = '%PDF-1.4 routing official document',
): array {
    $owner = User::factory()->create();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Forum '.Str::upper(Str::random(8));
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->external_letter_number = 'EXT/'.Str::upper(Str::random(8));
    $submission->external_letter_date = '2026-08-29';
    $submission->subject = $subject;
    $submission->summary = 'Ringkasan surat untuk pengujian routing.';
    $submission->submitted_at = now()->subDays(2);
    $submission->save();

    $path = $submission->public_id.'/'.Str::uuid().'.pdf';
    Storage::disk('submission-documents')->put($path, $contents);

    $sourceDocument = new SubmissionDocument;
    $sourceDocument->letter_submission_id = $submission->getKey();
    $sourceDocument->storage_disk = 'submission-documents';
    $sourceDocument->storage_path = $path;
    $sourceDocument->original_filename = 'surat-routing-'.Str::lower(Str::random(6)).'.pdf';
    $sourceDocument->mime_type = 'application/pdf';
    $sourceDocument->size_bytes = strlen($contents);
    $sourceDocument->sha256 = hash('sha256', $contents);
    $sourceDocument->uploaded_by_user_id = $owner->getKey();
    $sourceDocument->save();

    $registrar = User::factory()->internal()->create();
    $registrarPosition = routingPosition(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );
    $registrarAssignment = routingAssignment($registrar, $registrarPosition);

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
    $letter->received_at = $receivedAt ?? now();
    $letter->status = $status;
    $letter->registered_by_user_id = $registrar->getKey();
    $letter->registered_by_position_assignment_id = $registrarAssignment->getKey();
    $letter->save();

    $document = new LetterDocument;
    $document->incoming_letter_id = $letter->getKey();
    $document->source_submission_document_id = $sourceDocument->getKey();
    $document->version_number = 1;
    $document->replaces_document_id = null;
    $document->storage_disk = $sourceDocument->storage_disk;
    $document->storage_path = $sourceDocument->storage_path;
    $document->original_filename = $sourceDocument->original_filename;
    $document->mime_type = $sourceDocument->mime_type;
    $document->size_bytes = $sourceDocument->size_bytes;
    $document->sha256 = $sourceDocument->sha256;
    $document->correction_reason = null;
    $document->uploaded_by_user_id = $sourceDocument->uploaded_by_user_id;
    $document->save();

    return [
        'letter' => $letter->refresh(),
        'document' => $document->refresh(),
        'submission_document' => $sourceDocument->refresh(),
    ];
}

function routingCorrection(
    IncomingLetter $letter,
    LetterDocument $replaces,
    User $actor,
    PositionAssignment $actorAssignment,
): LetterDocument {
    $contents = '%PDF-1.4 corrected current routing document';
    $path = 'letter-documents/'.$letter->getKey().'/'.Str::uuid().'.pdf';
    Storage::disk('letter-documents')->put($path, $contents);

    $document = new LetterDocument;
    $document->incoming_letter_id = $letter->getKey();
    $document->source_submission_document_id = null;
    $document->version_number = $replaces->version_number + 1;
    $document->replaces_document_id = $replaces->getKey();
    $document->storage_disk = 'letter-documents';
    $document->storage_path = $path;
    $document->original_filename = 'surat-routing-terkini.pdf';
    $document->mime_type = 'application/pdf';
    $document->size_bytes = strlen($contents);
    $document->sha256 = hash('sha256', $contents);
    $document->correction_reason = 'Koreksi dokumen sebelum routing awal.';
    $document->uploaded_by_user_id = $actor->getKey();
    $document->save();

    app(RecordAudit::class)->execute(
        actor: $actor,
        action: AuditAction::DocumentVersionCreated,
        subjectType: 'letter_document',
        subjectId: $document->getKey(),
        newValues: [
            'incoming_letter_id' => $letter->getKey(),
            'version_number' => $document->version_number,
            'sha256' => $document->sha256,
        ],
        actorPositionAssignment: $actorAssignment,
    );

    return $document->refresh();
}

test('routing permission and business position boundaries return 403 or 404', function (): void {
    $letter = routingLetter()['letter'];
    $withoutPermission = routingActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [],
    );

    $this->actingAs($withoutPermission['user'])
        ->get(route('back-office.letter-routing.index'))
        ->assertForbidden();

    $assistant = routingActor(
        'ASSISTANT',
        null,
        [PermissionName::ViewLetterRouting],
    );

    $this->actingAs($assistant['user'])
        ->get(route('back-office.letter-routing.show', $letter))
        ->assertNotFound();

    $otherHead = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        'BAGIAN_HUKUM',
        [PermissionName::ViewLetterRouting, PermissionName::CreateLetterRouting],
    );

    $this->actingAs($otherHead['user'])
        ->get(route('back-office.letter-routing.index'))
        ->assertNotFound();

    $this->actingAs($otherHead['user'])
        ->post(route('back-office.letter-routing.store', $letter), [
            'target_position_id' => routingExecutive()['position']->getKey(),
        ])
        ->assertNotFound();

    $superAdmin = User::factory()->internal()->withTwoFactor()->create();
    PositionAssignmentTestData::grantSuperAdminRole($superAdmin);

    $this->actingAs($superAdmin)
        ->get(route('back-office.letter-routing.index'))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('back-office.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false));
});

test('general affairs routing query filters at the database and keeps summary unfiltered', function (): void {
    $registered = routingLetter('Koordinasi penanganan banjir', receivedAt: now()->subHour());
    $routed = routingLetter('Undangan rapat pimpinan', receivedAt: now()->subHours(2));
    routingLetter(
        'Surat yang tidak boleh tampil',
        IncomingLetterStatus::InProgress,
        now()->subHours(3),
        '%PDF-1.4 hidden routing document',
    );
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting, PermissionName::CreateLetterRouting],
    );
    $executive = routingExecutive();
    app(RouteIncomingLetter::class)->execute(
        $head['user'],
        $routed['letter'],
        $executive['position']->getKey(),
    );
    $staff = routingActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting],
    );

    $this->actingAs($staff['user'])
        ->get(route('back-office.letter-routing.index', [
            'search' => $registered['document']->original_filename,
            'status' => IncomingLetterStatus::Registered->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/letter-routing/Index')
            ->has('letters.data', 1)
            ->where('letters.data.0.id', $registered['letter']->getKey())
            ->where('letters.data.0.status', IncomingLetterStatus::Registered->value)
            ->where('letters.data.0.current_document.version_number', 1)
            ->where('summary.awaiting_route', 1)
            ->where('summary.pending_executive', 1)
            ->where('summary.routed_today', 1)
            ->where('auth.capabilities.can_view_letter_routing', true)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->missing('letters.data.0.current_document.storage_disk')
            ->missing('letters.data.0.current_document.storage_path')
            ->missing('letters.data.0.current_document.uploaded_by.email'));
});

test('general affairs head creates one initial route with an atomic audit and historical actor assignment', function (): void {
    $fixture = routingLetter();
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [
            PermissionName::ViewLetterRouting,
            PermissionName::CreateLetterRouting,
            PermissionName::ViewLetterActivities,
        ],
    );
    $executive = routingExecutive('Wali Kota');
    $correction = routingCorrection(
        $fixture['letter'],
        $fixture['document'],
        $head['user'],
        $head['assignment'],
    );

    $this->actingAs($head['user'])
        ->post(route('back-office.letter-routing.store', $fixture['letter']), [
            'target_position_id' => $executive['position']->getKey(),
        ])
        ->assertRedirect(route('back-office.letter-routing.show', $fixture['letter']));

    $route = LetterRoute::query()->firstOrFail();
    expect($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Routed)
        ->and($route->status)->toBe(LetterRouteStatus::Pending)
        ->and($route->recipient_position_id)->toBe($executive['position']->getKey())
        ->and($route->routed_by_user_id)->toBe($head['user']->getKey())
        ->and($route->routed_by_position_assignment_id)->toBe($head['assignment']->getKey())
        ->and($route->completed_at)->toBeNull();

    $audit = AuditLog::query()
        ->where('action', AuditAction::LetterRouted->value)
        ->where('subject_type', 'letter_route')
        ->where('subject_id', $route->getKey())
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($head['user']->getKey())
        ->and($audit->actor_position_assignment_id)->toBe($head['assignment']->getKey())
        ->and($audit->old_values['letter_status'])->toBe(IncomingLetterStatus::Registered->value)
        ->and($audit->new_values['letter_status'])->toBe(IncomingLetterStatus::Routed->value)
        ->and($audit->new_values['route_status'])->toBe(LetterRouteStatus::Pending->value)
        ->and($audit->metadata['document_version_number'])->toBe(2);

    $this->actingAs($head['user'])
        ->get(route('back-office.letter-routing.show', $fixture['letter']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/letter-routing/Show')
            ->where('letter.current_route.target_position.name', 'Wali Kota')
            ->where('letter.current_route.routed_by.name', $head['user']->name)
            ->where('letter.current_route.routed_by.position', 'Kepala Bagian Umum')
            ->where('letter.current_document.version_number', 2)
            ->where('letter.current_document.original_filename', $correction->original_filename)
            ->where('capabilities.can_route', false)
            ->where('routes.store', route('back-office.letter-routing.store', $fixture['letter']))
            ->missing('letter.current_route.routed_by.email')
            ->missing('letter.current_route.actor_position_assignment_id'));

    $this->actingAs($head['user'])
        ->get(route('back-office.letter-activities.index', [
            'action' => AuditAction::LetterRouted->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.action', AuditAction::LetterRouted->value)
            ->where('activities.data.0.target.agenda_number', $fixture['letter']->agenda_number)
            ->where('activities.data.0.target.subject', $fixture['letter']->subject)
            ->where('activities.data.0.before.status_surat', 'Terdaftar')
            ->where('activities.data.0.after.status_surat', 'Telah diarahkan')
            ->where('activities.data.0.after.status_routing', 'Menunggu disposisi pimpinan')
            ->where('filters.action', AuditAction::LetterRouted->value));
});

test('routing rejects staff, unavailable targets, and invalid request input without changing the letter', function (): void {
    $fixture = routingLetter();
    $staff = routingActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting],
    );
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting, PermissionName::CreateLetterRouting],
    );
    $vacantExecutive = routingPosition(
        OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
        null,
        'Wali Kota tanpa pejabat aktif',
    );
    $assistant = routingPosition('ASSISTANT');

    $this->actingAs($staff['user'])
        ->post(route('back-office.letter-routing.store', $fixture['letter']), [
            'target_position_id' => $vacantExecutive->getKey(),
        ])
        ->assertForbidden();

    foreach ([$vacantExecutive, $assistant] as $target) {
        $this->actingAs($head['user'])
            ->from(route('back-office.letter-routing.show', $fixture['letter']))
            ->post(route('back-office.letter-routing.store', $fixture['letter']), [
                'target_position_id' => $target->getKey(),
            ])
            ->assertSessionHasErrors('target_position_id');
    }

    $this->actingAs($head['user'])
        ->withHeader('Accept', 'application/json')
        ->postJson(route('back-office.letter-routing.store', $fixture['letter']), [
            'target_position_id' => 'bukan-id',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('target_position_id');

    expect($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Registered)
        ->and(LetterRoute::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditAction::LetterRouted->value)->count())->toBe(0);
});

test('routing action rechecks state and prevents rerouting even with stale authorized input', function (): void {
    $first = routingLetter();
    $second = routingLetter(
        'Surat sudah berjalan',
        IncomingLetterStatus::InProgress,
        contents: '%PDF-1.4 already in progress',
    );
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting, PermissionName::CreateLetterRouting],
    );
    $executive = routingExecutive();
    $action = app(RouteIncomingLetter::class);

    $action->execute($head['user'], $first['letter'], $executive['position']->getKey());

    expect(fn () => $action->execute(
        $head['user'],
        $first['letter'],
        $executive['position']->getKey(),
    ))->toThrow(InitialLetterRoutingStateConflict::class)
        ->and(fn () => $action->execute(
            $head['user'],
            $second['letter'],
            $executive['position']->getKey(),
        ))->toThrow(InitialLetterRoutingStateConflict::class)
        ->and(LetterRoute::query()->count())->toBe(1);

    $this->actingAs($head['user'])
        ->post(route('back-office.letter-routing.store', $first['letter']), [
            'target_position_id' => $executive['position']->getKey(),
        ])
        ->assertNotFound();
});

test('routing database state rolls back when the atomic audit cannot be written', function (): void {
    $fixture = routingLetter();
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateLetterRouting],
    );
    $executive = routingExecutive();

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new RuntimeException('Simulated audit failure.'));
    });

    expect(fn () => app(RouteIncomingLetter::class)->execute(
        $head['user'],
        $fixture['letter'],
        $executive['position']->getKey(),
    ))->toThrow(RuntimeException::class, 'Simulated audit failure.');

    expect($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Registered)
        ->and(LetterRoute::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditAction::LetterRouted->value)->count())->toBe(0);
});

test('letter route history is immutable except for its single pending to completed transition', function (): void {
    $fixture = routingLetter();
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateLetterRouting],
    );
    $executive = routingExecutive();
    $otherExecutive = routingExecutive('Wali Kota');
    $route = app(RouteIncomingLetter::class)->execute(
        $head['user'],
        $fixture['letter'],
        $executive['position']->getKey(),
    );

    expect(fn () => $route->forceFill([
        'recipient_position_id' => $otherExecutive['position']->getKey(),
    ])->save())->toThrow(LogicException::class)
        ->and(fn () => $route->fresh()->delete())->toThrow(LogicException::class);

    $completion = $route->fresh();
    $completion->status = LetterRouteStatus::Completed;
    $completion->completed_at = now();
    $completion->save();

    expect($completion->refresh()->status)->toBe(LetterRouteStatus::Completed)
        ->and($completion->recipient_position_id)->toBe($executive['position']->getKey())
        ->and($completion->completed_at)->not->toBeNull();
});

test('executive inbox is scoped to the active recipient position and streams the private current PDF', function (): void {
    $fixture = routingLetter();
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateLetterRouting],
    );
    $recipient = routingExecutive('Wali Kota');
    $otherExecutive = routingExecutive('Sekretaris Daerah');
    routingGrant($recipient['user'], PermissionName::ViewExecutiveInbox);
    routingGrant($otherExecutive['user'], PermissionName::ViewExecutiveInbox);
    $route = app(RouteIncomingLetter::class)->execute(
        $head['user'],
        $fixture['letter'],
        $recipient['position']->getKey(),
    );

    $this->actingAs($recipient['user'])
        ->get(route('back-office.executive.inbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/executive/inbox/Index')
            ->has('inbox.data', 1)
            ->where('inbox.data.0.route_id', $route->getKey())
            ->where('summary.pending', 1)
            ->where('summary.received_today', 1)
            ->where('auth.capabilities.can_view_executive_inbox', true)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->missing('inbox.data.0.letter.current_document.storage_disk')
            ->missing('inbox.data.0.letter.current_document.storage_path'));

    $preview = $this->actingAs($recipient['user'])
        ->get(route('back-office.executive.inbox.document.preview', $route));
    $preview
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    expect($preview->headers->get('Content-Disposition'))->toStartWith('inline;');

    $download = $this->actingAs($recipient['user'])
        ->get(route('back-office.executive.inbox.document.download', $route));
    $download->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($download->headers->get('Content-Disposition'))->toStartWith('attachment;');

    $this->actingAs($otherExecutive['user'])
        ->get(route('back-office.executive.inbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('inbox.data', 0));
    $this->actingAs($otherExecutive['user'])
        ->get(route('back-office.executive.inbox.show', $route))
        ->assertNotFound();
    $this->actingAs($otherExecutive['user'])
        ->get(route('back-office.executive.inbox.document.preview', $route))
        ->assertNotFound();

    Storage::disk($fixture['document']->storage_disk)->delete($fixture['document']->storage_path);
    $this->actingAs($recipient['user'])
        ->get(route('back-office.executive.inbox.document.preview', $route))
        ->assertConflict();
});

test('executive inbox permission does not bypass executive position context', function (): void {
    $assistant = routingActor(
        'ASSISTANT',
        null,
        [PermissionName::ViewExecutiveInbox],
    );

    $this->actingAs($assistant['user'])
        ->get(route('back-office.executive.inbox.index'))
        ->assertNotFound();

    $unprivilegedExecutive = routingExecutive();
    $this->actingAs($unprivilegedExecutive['user'])
        ->get(route('back-office.executive.inbox.index'))
        ->assertForbidden();
});

test('routing and inbox list filters reject malformed server input', function (): void {
    $staff = routingActor(
        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::ViewLetterRouting],
    );
    $executive = routingExecutive();
    routingGrant($executive['user'], PermissionName::ViewExecutiveInbox);

    $this->actingAs($staff['user'])
        ->getJson(route('back-office.letter-routing.index', [
            'search' => ['invalid'],
            'status' => IncomingLetterStatus::Completed->value,
            'page' => 0,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['search', 'status', 'page']);

    $this->actingAs($executive['user'])
        ->getJson(route('back-office.executive.inbox.index', [
            'date_from' => '2026-09-02',
            'date_to' => '2026-09-01',
            'page' => 0,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date_to', 'page']);
});

test('initial routing mutation is rate limited per authenticated user', function (): void {
    $fixture = routingLetter();
    $head = routingActor(
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
        [PermissionName::CreateLetterRouting],
    );

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->actingAs($head['user'])
            ->post(route('back-office.letter-routing.store', $fixture['letter']), [])
            ->assertSessionHasErrors('target_position_id');
    }

    $this->actingAs($head['user'])
        ->post(route('back-office.letter-routing.store', $fixture['letter']), [])
        ->assertTooManyRequests();

    expect($fixture['letter']->refresh()->status)->toBe(IncomingLetterStatus::Registered)
        ->and(LetterRoute::query()->count())->toBe(0);
});
