<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Date::setTestNow('2026-08-30 04:00:00');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

afterEach(function (): void {
    Date::setTestNow();
});

function letterActivityGrantPermission(User $user, string $roleName): void
{
    $permission = Permission::findOrCreate(
        PermissionName::ViewLetterActivities->value,
        AuthorizationCatalog::GUARD_NAME,
    );
    $role = Role::findOrCreate($roleName, AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions([$permission]);
    $user->assignRole($role);
}

function letterActivityAssignment(
    User $user,
    string $levelCode,
    ?string $unitCode = null,
): PositionAssignment {
    $level = PositionLevel::query()->where('code', $levelCode)->first();

    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = $levelCode;
        $level->name = $levelCode;
        $level->hierarchy_order = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL ? 40 : 20;
        $level->is_active = true;
        $level->save();
    }

    $unit = null;

    if ($unitCode !== null) {
        $unit = OrganizationalUnit::query()->where('code', $unitCode)->first();

        if (! $unit instanceof OrganizationalUnit) {
            $unit = new OrganizationalUnit;
            $unit->code = $unitCode;
            $unit->name = $unitCode;
            $unit->is_active = true;
            $unit->save();
        }
    }

    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->organizational_unit_id = $unit?->getKey();
    $position->code = 'ACTIVITY-'.Str::upper(Str::random(10));
    $position->name = $levelCode === OrganizationCatalog::SECTION_HEAD_LEVEL
        ? 'Kepala Bagian Umum'
        : 'Sekretaris Daerah';
    $position->is_active = true;
    $position->save();

    $assignment = new PositionAssignment;
    $assignment->user_id = $user->getKey();
    $assignment->position_id = $position->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->assigned_by_user_id = null;
    $assignment->save();

    return $assignment;
}

function letterActivitySubmission(User $owner, string $subject = 'Permohonan audiensi banjir'): LetterSubmission
{
    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Submitted;
    $submission->submitted_by_user_id = $owner->getKey();
    $submission->sender_organization_name = 'Forum Warga Pesisir';
    $submission->contact_name = $owner->name;
    $submission->contact_email = $owner->email;
    $submission->subject = $subject;
    $submission->submitted_at = now();
    $submission->save();

    return $submission;
}

function letterActivityAudit(
    User $actor,
    LetterSubmission $submission,
    AuditAction $action,
    string $createdAt,
    ?PositionAssignment $assignment = null,
): AuditLog {
    $audit = new AuditLog;
    $audit->actor_user_id = $actor->getKey();
    $audit->actor_position_assignment_id = $assignment?->getKey();
    $audit->action = $action->value;
    $audit->subject_type = 'letter_submission';
    $audit->subject_id = $submission->getKey();
    $audit->old_values = ['status' => SubmissionStatus::Draft->value];
    $audit->new_values = [
        'status' => SubmissionStatus::Submitted->value,
        'password' => 'tidak-boleh-bocor',
    ];
    $audit->metadata = [
        'public_id' => $submission->public_id,
        'token' => 'tidak-boleh-bocor',
    ];
    $audit->request_id = 'req-letter-activity-'.Str::lower(Str::random(12));
    $audit->ip_address = '192.168.10.20';
    $audit->user_agent = "Chrome 128\nWindows";
    $audit->created_at = Date::parse($createdAt);
    $audit->save();

    return $audit;
}

test('letter activity route enforces portal permission and sanitized super admin boundaries', function (): void {
    expect(route('back-office.letter-activities.index', absolute: false))
        ->toBe('/back-office/audits/letters');

    $this->get(route('back-office.letter-activities.index'))
        ->assertRedirect(route('back-office.login'));

    $publicUser = User::factory()->create();
    letterActivityGrantPermission($publicUser, 'public-letter-auditor');

    $this->actingAs($publicUser)
        ->get(route('back-office.letter-activities.index'))
        ->assertNotFound();

    $this->actingAs(User::factory()->internal()->create())
        ->get(route('back-office.letter-activities.index'))
        ->assertForbidden();

    $owner = User::factory()->create(['name' => 'Pemohon Rahasia']);
    $submission = letterActivitySubmission($owner, 'Surat yang bersifat privat');
    letterActivityAudit(
        $owner,
        $submission,
        AuditAction::SubmissionSubmitted,
        '2026-08-29 16:30:00',
    );
    $administrator = User::factory()->internal()->withTwoFactor()->create();
    PositionAssignmentTestData::grantSuperAdminRole($administrator);

    $this->actingAs($administrator)
        ->get(route('back-office.letter-activities.index', [
            'actor' => $owner->getKey(),
            'letter' => 'Surat yang bersifat privat',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/letter-activities/Index')
            ->where('auth.capabilities.can_view_letter_activities', true)
            ->where('visibility', 'summary')
            ->where('filters.actor', '')
            ->where('filters.letter', '')
            ->where('activities.data.0.actor.id', null)
            ->where('activities.data.0.actor.name', 'Pengguna portal publik')
            ->where('activities.data.0.target', null)
            ->where('activities.data.0.before', null)
            ->where('activities.data.0.after', null)
            ->where('activities.data.0.request_id', null)
            ->where('activities.data.0.ip_address', null)
            ->where('activities.data.0.user_agent', null)
            ->has('filterOptions.actors', 0)
            ->where('summary.total', 1));
});

test('eligible business positions receive sanitized allowlisted activity details', function (): void {
    $owner = User::factory()->create(['name' => 'Andi Pemohon']);
    $submission = letterActivitySubmission($owner);
    letterActivityAudit(
        $owner,
        $submission,
        AuditAction::SubmissionSubmitted,
        '2026-08-29 16:30:00',
    );

    $head = User::factory()->internal()->create();
    letterActivityGrantPermission($head, 'letter-activity-head');
    letterActivityAssignment(
        $head,
        OrganizationCatalog::SECTION_HEAD_LEVEL,
        OrganizationCatalog::GENERAL_AFFAIRS_UNIT,
    );

    $executive = User::factory()->internal()->create();
    letterActivityGrantPermission($executive, 'letter-activity-executive');
    letterActivityAssignment($executive, OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL);

    foreach ([$head, $executive] as $viewer) {
        $this->actingAs($viewer)
            ->get(route('back-office.letter-activities.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('visibility', 'details')
                ->where('activities.data.0.actor.id', $owner->getKey())
                ->where('activities.data.0.actor.name', 'Andi Pemohon')
                ->where('activities.data.0.target.public_id', $submission->public_id)
                ->where('activities.data.0.target.subject', $submission->subject)
                ->where('activities.data.0.target.sender', 'Forum Warga Pesisir')
                ->where('activities.data.0.before.status', 'Draf')
                ->where('activities.data.0.after.status', 'Diajukan')
                ->where('activities.data.0.ip_address', '192.168.10.20')
                ->where('activities.data.0.user_agent', 'Chrome 128 Windows')
                ->missing('activities.data.0.after.password')
                ->missing('activities.data.0.metadata'));
    }
});

test('letter activities apply action source actor target date and pagination filters on the server', function (): void {
    $viewer = User::factory()->internal()->create();
    letterActivityGrantPermission($viewer, 'letter-activity-filter-viewer');
    letterActivityAssignment($viewer, OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL);
    $owner = User::factory()->create(['name' => 'Pemohon Banjir']);
    $submission = letterActivitySubmission($owner, 'Koordinasi penanggulangan banjir');

    letterActivityAudit($owner, $submission, AuditAction::SubmissionSubmitted, '2026-08-29 15:59:59');

    foreach (range(1, 21) as $minute) {
        letterActivityAudit(
            $owner,
            $submission,
            AuditAction::SubmissionSubmitted,
            Date::parse('2026-08-29 16:00:00')->addMinutes($minute)->toDateTimeString(),
        );
    }

    letterActivityAudit($owner, $submission, AuditAction::SubmissionCreated, '2026-08-29 18:00:00');
    letterActivityAudit($owner, $submission, AuditAction::SubmissionSubmitted, '2026-08-30 16:00:00');

    $this->actingAs($viewer)
        ->get(route('back-office.letter-activities.index', [
            'action' => AuditAction::SubmissionSubmitted->value,
            'source' => 'public',
            'actor' => $owner->getKey(),
            'letter' => 'penanggulangan banjir',
            'date_from' => '2026-08-30',
            'date_to' => '2026-08-30',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 21)
            ->where('summary.received', 21)
            ->where('activities.meta.current_page', 1)
            ->where('activities.meta.last_page', 2)
            ->where('activities.meta.per_page', 20)
            ->where('activities.meta.total', 21)
            ->has('activities.data', 20));

    $this->actingAs($viewer)
        ->getJson(route('back-office.letter-activities.index', [
            'date_from' => '2026-08-31',
            'date_to' => '2026-08-30',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date_to');
});
