<?php

use App\Actions\AddDispositionFollowUp;
use App\Actions\CompleteDispositionBranch;
use App\Actions\StartDispositionBranch;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\SenderOrganization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\OrganizationAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Date::setTestNow('2026-09-02 04:00:00 UTC');
    $this->seed(OrganizationAndUserSeeder::class);
    Date::setTestNow('2026-09-02 04:00:02 UTC');

    RateLimiter::clear('report-export:ip:127.0.0.1');

    User::query()->each(function (User $user): void {
        RateLimiter::clear('report-export:user:'.$user->getAuthIdentifier());
    });
});

afterEach(function (): void {
    Date::setTestNow();
});

function m7ReportUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

function m7ReportPosition(string $code): Position
{
    return Position::query()->where('code', $code)->firstOrFail();
}

function m7ReportAssignment(User $user, Position $position): PositionAssignment
{
    return PositionAssignment::query()
        ->where('user_id', $user->getKey())
        ->where('position_id', $position->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
}

/**
 * @return array{letter: IncomingLetter, route: LetterRoute, terminals: array<string, DispositionRecipient>}
 */
function m7ReportLetter(
    string $executiveCode,
    string $assistantCode,
    array $sectionCodes,
    CarbonImmutable $receivedAt,
    string $senderName = 'Dinas Pelayanan Terpadu',
    string $subject = 'Koordinasi program pelayanan',
    SubmissionSource $source = SubmissionSource::Online,
): array {
    $registrar = m7ReportUser('kabag.umum@internal.test');
    $registrarPosition = m7ReportPosition('KABAG_UMUM');
    $registrarAssignment = m7ReportAssignment($registrar, $registrarPosition);
    $executive = m7ReportPosition($executiveCode);
    $executiveAssignment = PositionAssignment::query()
        ->where('position_id', $executive->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
    $executiveUser = $executiveAssignment->user;
    $assistant = m7ReportPosition($assistantCode);
    $assistantAssignment = PositionAssignment::query()
        ->where('position_id', $assistant->getKey())
        ->whereNull('ended_at')
        ->firstOrFail();
    $assistantUser = $assistantAssignment->user;

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = $source;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = $source === SubmissionSource::Online
        ? User::factory()->create()->getKey()
        : null;
    $submission->recorded_by_user_id = $source === SubmissionSource::Manual
        ? $registrar->getKey()
        : null;
    $submission->sender_organization_name = $senderName;
    $submission->contact_name = 'Pengirim laporan';
    $submission->contact_email = 'pengirim@example.test';
    $submission->external_letter_number = 'EXT/'.Str::upper(Str::random(10));
    $submission->external_letter_date = $receivedAt->toDateString();
    $submission->subject = $subject;
    $submission->summary = 'Ringkasan pengujian laporan periodik.';
    $submission->submitted_at = $receivedAt->subDay();
    $submission->save();

    $sender = new SenderOrganization;
    $sender->name = $senderName;
    $sender->is_active = true;
    $sender->save();

    $letter = new IncomingLetter;
    $letter->letter_submission_id = $submission->getKey();
    $letter->agenda_number = 'M7/'.Str::upper(Str::random(10));
    $letter->agenda_year = (int) $receivedAt->format('Y');
    $letter->sender_organization_id = $sender->getKey();
    $letter->external_letter_number = $submission->external_letter_number;
    $letter->external_letter_date = $submission->external_letter_date;
    $letter->subject = $subject;
    $letter->summary = $submission->summary;
    $letter->received_at = $receivedAt;
    $letter->status = IncomingLetterStatus::InProgress;
    $letter->registered_by_user_id = $registrar->getKey();
    $letter->registered_by_position_assignment_id = $registrarAssignment->getKey();
    $letter->save();

    $route = new LetterRoute;
    $route->incoming_letter_id = $letter->getKey();
    $route->recipient_position_id = $executive->getKey();
    $route->routed_by_user_id = $registrar->getKey();
    $route->routed_by_position_assignment_id = $registrarAssignment->getKey();
    $route->status = LetterRouteStatus::Completed;
    $route->routed_at = $receivedAt->addHour();
    $route->completed_at = $receivedAt->addHours(2);
    $route->save();

    $instruction = InstructionLabel::query()->where('code', 'FOLLOW_UP')->firstOrFail();
    $initialDisposition = new Disposition;
    $initialDisposition->incoming_letter_id = $letter->getKey();
    $initialDisposition->source_route_id = $route->getKey();
    $initialDisposition->parent_recipient_id = null;
    $initialDisposition->created_by_user_id = $executiveUser->getKey();
    $initialDisposition->created_by_position_assignment_id = $executiveAssignment->getKey();
    $initialDisposition->instruction_note = 'Teruskan sesuai kewenangan bidang.';
    $initialDisposition->created_at = $receivedAt->addHours(2);
    $initialDisposition->save();
    $initialDisposition->instructionLabels()->attach($instruction->getKey());

    $assistantRecipient = new DispositionRecipient;
    $assistantRecipient->disposition_id = $initialDisposition->getKey();
    $assistantRecipient->recipient_position_id = $assistant->getKey();
    $assistantRecipient->status = DispositionRecipientStatus::Pending;
    $assistantRecipient->received_at = $receivedAt->addHours(2);
    $assistantRecipient->started_at = null;
    $assistantRecipient->completed_at = null;
    $assistantRecipient->completed_by_user_id = null;
    $assistantRecipient->completed_by_position_assignment_id = null;
    $assistantRecipient->completion_note = null;
    $assistantRecipient->save();

    $childDisposition = new Disposition;
    $childDisposition->incoming_letter_id = $letter->getKey();
    $childDisposition->source_route_id = null;
    $childDisposition->parent_recipient_id = $assistantRecipient->getKey();
    $childDisposition->created_by_user_id = $assistantUser->getKey();
    $childDisposition->created_by_position_assignment_id = $assistantAssignment->getKey();
    $childDisposition->instruction_note = 'Tangani dan catat hasil pada cabang masing-masing.';
    $childDisposition->created_at = $receivedAt->addHours(3);
    $childDisposition->save();
    $childDisposition->instructionLabels()->attach($instruction->getKey());

    $assistantRecipient->status = DispositionRecipientStatus::Completed;
    $assistantRecipient->completed_at = $receivedAt->addHours(3);
    $assistantRecipient->completed_by_user_id = $assistantUser->getKey();
    $assistantRecipient->completed_by_position_assignment_id = $assistantAssignment->getKey();
    $assistantRecipient->completion_note = null;
    $assistantRecipient->save();

    $terminals = [];

    foreach ($sectionCodes as $sectionCode) {
        $section = m7ReportPosition($sectionCode);
        $recipient = new DispositionRecipient;
        $recipient->disposition_id = $childDisposition->getKey();
        $recipient->recipient_position_id = $section->getKey();
        $recipient->status = DispositionRecipientStatus::Pending;
        $recipient->received_at = $receivedAt->addHours(3);
        $recipient->started_at = null;
        $recipient->completed_at = null;
        $recipient->completed_by_user_id = null;
        $recipient->completed_by_position_assignment_id = null;
        $recipient->completion_note = null;
        $recipient->save();
        $terminals[$sectionCode] = $recipient;
    }

    return compact('letter', 'route', 'terminals');
}

/** @return array<string, string> */
function m7ReportPeriod(array $overrides = []): array
{
    return [
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-30',
        'event' => 'RECEIVED',
        ...$overrides,
    ];
}

test('report routes enforce permission and business Position boundaries', function (): void {
    $this->actingAs(m7ReportUser('petugas.surat@internal.test'))
        ->get(route('back-office.reports.index'))
        ->assertForbidden();

    $superAdmin = User::factory()->internal()->withTwoFactor()->create();
    PositionAssignmentTestData::grantSuperAdminRole($superAdmin);

    $this->actingAs($superAdmin)
        ->get(route('back-office.reports.index'))
        ->assertNotFound();

    foreach ([
        'wali.kota@internal.test',
        'sekda@internal.test',
        'asisten.1@internal.test',
        'kabag.umum@internal.test',
        'kabag.kesra@internal.test',
    ] as $email) {
        $this->actingAs(m7ReportUser($email))
            ->get(route('back-office.reports.index'))
            ->assertOk();
    }

    $this->actingAs(m7ReportUser('asisten.1@internal.test'))
        ->get(route('back-office.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_reports', true)
            ->where('auth.capabilities.can_export_reports', true));
});

test('database scopes isolate assistant subtrees and section head branches', function (): void {
    $first = m7ReportLetter(
        'SEKDA',
        'ASISTEN-I',
        ['KABAG_KESRA', 'KABAG_TAPEM'],
        CarbonImmutable::parse('2026-09-01 01:00:00 UTC'),
    );
    $second = m7ReportLetter(
        'WALI_KOTA',
        'ASISTEN-II',
        ['KABAG_EKONOMI'],
        CarbonImmutable::parse('2026-09-02 01:00:00 UTC'),
    );
    $generalAffairs = m7ReportLetter(
        'SEKDA',
        'ASISTEN-III',
        ['KABAG_UMUM'],
        CarbonImmutable::parse('2026-09-03 01:00:00 UTC'),
    );

    $kesraBranch = $first['terminals']['KABAG_KESRA'];
    $tapemBranch = $first['terminals']['KABAG_TAPEM'];
    app(StartDispositionBranch::class)->execute(m7ReportUser('kabag.kesra@internal.test'), $kesraBranch);
    app(AddDispositionFollowUp::class)->execute(
        m7ReportUser('kabag.kesra@internal.test'),
        $kesraBranch,
        'Catatan internal cabang kesejahteraan rakyat.',
    );
    app(StartDispositionBranch::class)->execute(m7ReportUser('kabag.tapem@internal.test'), $tapemBranch);
    app(AddDispositionFollowUp::class)->execute(
        m7ReportUser('kabag.tapem@internal.test'),
        $tapemBranch,
        'RAHASIA-CABANG-TAPEM tidak boleh bocor ke cabang saudara.',
    );

    $this->actingAs(m7ReportUser('asisten.1@internal.test'))
        ->get(route('back-office.reports.index', m7ReportPeriod()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('letters.pagination.total', 1)
            ->where('letters.data.0.agenda_number', $first['letter']->agenda_number));

    $this->actingAs(m7ReportUser('asisten.1@internal.test'))
        ->get(route('back-office.reports.show', [
            'incomingLetter' => $second['letter'],
            ...m7ReportPeriod(),
        ]))
        ->assertNotFound();

    $sectionResponse = $this->actingAs(m7ReportUser('kabag.kesra@internal.test'))
        ->get(route('back-office.reports.show', [
            'incomingLetter' => $first['letter'],
            ...m7ReportPeriod(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.branches', 1)
            ->has('report.branches.0.children', 1)
            ->where('report.branches.0.children.0.recipient_position.code', 'KABAG_KESRA')
            ->where('report.branches.0.children.0.follow_ups.0.note', 'Catatan internal cabang kesejahteraan rakyat.')
            ->missing('report.letter.storage_disk')
            ->missing('report.letter.storage_path')
            ->missing('report.branches.0.children.0.created_by_position_assignment_id')
            ->missing('report.branches.0.children.0.follow_ups.0.created_by.email'));
    $sectionResponse->assertDontSee('RAHASIA-CABANG-TAPEM', false);

    $tapemPosition = m7ReportPosition('KABAG_TAPEM');
    $oldTapemAssignment = m7ReportAssignment(m7ReportUser('kabag.tapem@internal.test'), $tapemPosition);
    $oldTapemAssignment->ended_at = now()->subSecond();
    $oldTapemAssignment->save();

    $unionAssignment = new PositionAssignment;
    $unionAssignment->user_id = m7ReportUser('kabag.kesra@internal.test')->getKey();
    $unionAssignment->position_id = $tapemPosition->getKey();
    $unionAssignment->started_at = now()->subSecond();
    $unionAssignment->ended_at = null;
    $unionAssignment->assigned_by_user_id = null;
    $unionAssignment->save();

    $this->actingAs(m7ReportUser('kabag.kesra@internal.test'))
        ->get(route('back-office.reports.show', [
            'incomingLetter' => $first['letter'],
            ...m7ReportPeriod(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.branches.0.children', 2));

    $this->actingAs(m7ReportUser('kabag.umum@internal.test'))
        ->get(route('back-office.reports.index', m7ReportPeriod()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.received_letters', 3)
            ->where('letters.pagination.total', 1)
            ->where('letters.data.0.agenda_number', $generalAffairs['letter']->agenda_number));

    $executiveResponse = $this->actingAs(m7ReportUser('wali.kota@internal.test'))
        ->get(route('back-office.reports.show', [
            'incomingLetter' => $first['letter'],
            ...m7ReportPeriod(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.branches.0.children', 2));
    $executiveResponse->assertSee('RAHASIA-CABANG-TAPEM', false);
});

test('event metrics use office timezone and terminal completion timestamp', function (): void {
    $boundaryLetter = m7ReportLetter(
        'SEKDA',
        'ASISTEN-I',
        ['KABAG_KESRA'],
        CarbonImmutable::parse('2026-08-31 16:00:00 UTC'),
    );

    Date::setTestNow('2026-09-04 02:00:00 UTC');
    app(CompleteDispositionBranch::class)->execute(
        m7ReportUser('kabag.kesra@internal.test'),
        $boundaryLetter['terminals']['KABAG_KESRA'],
        'Seluruh tindak lanjut telah selesai diverifikasi.',
    );

    $this->actingAs(m7ReportUser('sekda@internal.test'))
        ->get(route('back-office.reports.index', m7ReportPeriod([
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
        ])))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.received_letters', 1)
            ->where('summary.completed_letters', 0));

    $this->actingAs(m7ReportUser('sekda@internal.test'))
        ->get(route('back-office.reports.index', m7ReportPeriod([
            'date_from' => '2026-09-04',
            'date_to' => '2026-09-04',
            'event' => 'COMPLETED',
        ])))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.received_letters', 0)
            ->where('summary.completed_letters', 1)
            ->where('letters.pagination.total', 1)
            ->where('letters.data.0.status', 'COMPLETED'));
});

test('invalid report filters return validation errors', function (): void {
    $this->actingAs(m7ReportUser('asisten.1@internal.test'))
        ->getJson(route('back-office.reports.index', [
            'date_from' => '2025-01-01',
            'date_to' => '2026-09-02',
            'event' => 'UNKNOWN',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date_to', 'event']);
});

test('csv exports stream authorized allowlists and neutralize spreadsheet formulas', function (): void {
    $letter = m7ReportLetter(
        'SEKDA',
        'ASISTEN-I',
        ['KABAG_KESRA'],
        CarbonImmutable::parse('2026-09-02 01:00:00 UTC'),
        '=HYPERLINK("https://example.test")',
        '+Perintah berbahaya',
        SubmissionSource::Manual,
    );
    app(StartDispositionBranch::class)->execute(
        m7ReportUser('kabag.kesra@internal.test'),
        $letter['terminals']['KABAG_KESRA'],
    );
    app(AddDispositionFollowUp::class)->execute(
        m7ReportUser('kabag.kesra@internal.test'),
        $letter['terminals']['KABAG_KESRA'],
        'CATATAN-RAHASIA tidak boleh masuk CSV.',
    );

    $response = $this->actingAs(m7ReportUser('kabag.kesra@internal.test'))
        ->get(route('back-office.reports.exports.letters', m7ReportPeriod()))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    $csv = $response->streamedContent();

    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain("'+Perintah berbahaya")
        ->and($csv)->not->toContain('CATATAN-RAHASIA')
        ->and($csv)->not->toContain('storage_disk')
        ->and($csv)->not->toContain('position_assignment_id')
        ->and($csv)->not->toContain('contact_email');

    $summaryResponse = $this->actingAs(m7ReportUser('kabag.kesra@internal.test'))
        ->get(route('back-office.reports.exports.summary', m7ReportPeriod()))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $summaryCsv = $summaryResponse->streamedContent();

    expect($summaryCsv)->toContain('LAPORAN PERIODIK SURAT')
        ->and($summaryCsv)->toContain('PERFORMA JABATAN')
        ->and($summaryCsv)->toContain('Rata-rata durasi (jam)')
        ->and($summaryCsv)->not->toContain('CATATAN-RAHASIA');
});

test('report export limiter rejects the eleventh request for one user', function (): void {
    $user = m7ReportUser('asisten.1@internal.test');
    $url = route('back-office.reports.exports.summary', m7ReportPeriod());

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->actingAs($user)->get($url)->assertOk();
    }

    $this->actingAs($user)->get($url)->assertTooManyRequests();
});
