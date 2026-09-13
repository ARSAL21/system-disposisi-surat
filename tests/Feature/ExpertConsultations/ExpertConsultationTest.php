<?php

use App\Actions\ReportExpertConsultation;
use App\Actions\RequestExpertConsultations;
use App\Enums\AuditAction;
use App\Enums\ExpertConsultationStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\ExpertConsultation;
use App\Models\IncomingLetter;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\SenderOrganization;
use App\Models\User;
use Database\Seeders\OrganizationAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Date::setTestNow('2026-09-14 08:00:00');
    Storage::fake('expert-consultation-documents');
    $this->seed(OrganizationAndUserSeeder::class);
});

test('assigned expert can submit one immutable report with a private pdf', function (): void {
    $mayor = User::query()->where('email', 'wali.kota@internal.test')->firstOrFail();
    $mayorPosition = Position::query()->where('code', 'WALI_KOTA')->firstOrFail();
    $mayorAssignment = PositionAssignment::query()->where('user_id', $mayor->getKey())->where('position_id', $mayorPosition->getKey())->firstOrFail();
    $route = expertConsultationRoute($mayor, $mayorPosition, $mayorAssignment);
    $expertPosition = Position::query()->where('code', 'STAF_AHLI_EKONOMI_PEMBANGUNAN')->firstOrFail();
    $consultation = app(RequestExpertConsultations::class)->execute($mayor, $route, [(int) $expertPosition->getKey()], null)->firstOrFail();
    $expert = User::query()->where('email', 'staf.ahli.ekonomi@internal.test')->firstOrFail();

    app(ReportExpertConsultation::class)->execute(
        $expert,
        $consultation,
        'Telaah menunjukkan kebutuhan koordinasi dan data pendukung yang lebih rinci.',
        'Lanjutkan setelah bahan teknis dan jadwal pembahasan disiapkan.',
        UploadedFile::fake()->create('telaah-ekonomi.pdf', 32, 'application/pdf'),
    );

    $consultation->refresh()->load('report', 'documents');
    expect($consultation->status)->toBe(ExpertConsultationStatus::Reported)
        ->and($consultation->report)->not->toBeNull()
        ->and($consultation->documents)->toHaveCount(1)
        ->and(AuditLog::query()->where('action', AuditAction::ExpertConsultationReported->value)->count())->toBe(1);
    Storage::disk('expert-consultation-documents')->assertExists($consultation->documents->firstOrFail()->storage_path);
});

afterEach(function (): void {
    Date::setTestNow();
});

test('mayor can atomically request an eligible expert consultation and duplicate pending work is rejected', function (): void {
    $mayor = User::query()->where('email', 'wali.kota@internal.test')->firstOrFail();
    $mayorPosition = Position::query()->where('code', 'WALI_KOTA')->firstOrFail();
    $mayorAssignment = PositionAssignment::query()->where('user_id', $mayor->getKey())->where('position_id', $mayorPosition->getKey())->firstOrFail();
    $route = expertConsultationRoute($mayor, $mayorPosition, $mayorAssignment);
    $expert = Position::query()->where('code', 'STAF_AHLI_EKONOMI_PEMBANGUNAN')->firstOrFail();

    $created = app(RequestExpertConsultations::class)->execute($mayor, $route, [(int) $expert->getKey()], 'Mohon telaah dampak ekonomi dan pembangunan.');

    expect($created)->toHaveCount(1)
        ->and(ExpertConsultation::query()->where('letter_route_id', $route->getKey())->firstOrFail()->status)->toBe(ExpertConsultationStatus::Pending)
        ->and(AuditLog::query()->where('action', AuditAction::ExpertConsultationRequested->value)->count())->toBe(1);

    expect(fn () => app(RequestExpertConsultations::class)->execute($mayor, $route, [(int) $expert->getKey()], null))
        ->toThrow(ValidationException::class);

    expect(ExpertConsultation::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::ExpertConsultationRequested->value)->count())->toBe(1);
});

function expertConsultationRoute(User $mayor, Position $mayorPosition, PositionAssignment $mayorAssignment): LetterRoute
{
    $sender = new SenderOrganization;
    $sender->name = 'Instansi Pengirim';
    $sender->save();

    $submission = new LetterSubmission;
    $submission->public_id = (string) Str::ulid();
    $submission->source = SubmissionSource::Online;
    $submission->status = SubmissionStatus::Registered;
    $submission->submitted_by_user_id = User::factory()->create()->getKey();
    $submission->sender_organization_name = $sender->name;
    $submission->contact_name = 'Pemohon';
    $submission->contact_email = 'pemohon@example.test';
    $submission->external_letter_number = 'EXT/001';
    $submission->external_letter_date = now()->subDay()->toDateString();
    $submission->subject = 'Permohonan telaah';
    $submission->summary = 'Ringkasan permohonan telaah.';
    $submission->submitted_at = now()->subDay();
    $submission->save();

    $letter = new IncomingLetter;
    $letter->letter_submission_id = $submission->getKey();
    $letter->agenda_number = 'AG-'.$submission->getKey();
    $letter->agenda_year = (int) now()->year;
    $letter->sender_organization_id = $sender->getKey();
    $letter->external_letter_number = $submission->external_letter_number;
    $letter->external_letter_date = $submission->external_letter_date;
    $letter->subject = $submission->subject;
    $letter->summary = $submission->summary;
    $letter->received_at = now();
    $letter->status = IncomingLetterStatus::Routed;
    $letter->registered_by_user_id = $mayor->getKey();
    $letter->registered_by_position_assignment_id = $mayorAssignment->getKey();
    $letter->save();

    $route = new LetterRoute;
    $route->incoming_letter_id = $letter->getKey();
    $route->recipient_position_id = $mayorPosition->getKey();
    $route->routed_by_user_id = $mayor->getKey();
    $route->routed_by_position_assignment_id = $mayorAssignment->getKey();
    $route->status = LetterRouteStatus::Pending;
    $route->routed_at = now();
    $route->completed_at = null;
    $route->save();

    return $route;
}
