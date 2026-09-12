<?php

namespace App\Http\Controllers\BackOffice\StandaloneOutgoing;

use App\Actions\AddStandaloneOutgoingDocumentVersion;
use App\Actions\CreateStandaloneOutgoingDraft;
use App\Actions\GetStandaloneOutgoingWorkspace;
use App\Actions\ReviewStandaloneOutgoingDraft;
use App\Actions\SubmitStandaloneOutgoingDraft;
use App\Actions\UpdateStandaloneOutgoingDraft;
use App\Enums\StandaloneOutgoingReviewDecision;
use App\Enums\StandaloneOutgoingReviewStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StandaloneOutgoing\ReviewStandaloneOutgoingDraftRequest;
use App\Http\Requests\BackOffice\StandaloneOutgoing\StoreStandaloneOutgoingDocumentVersionRequest;
use App\Http\Requests\BackOffice\StandaloneOutgoing\StoreStandaloneOutgoingDraftRequest;
use App\Http\Requests\BackOffice\StandaloneOutgoing\UpdateStandaloneOutgoingDraftRequest;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\Position;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class StandaloneOutgoingDraftController extends Controller
{
    public function index(Request $request, GetStandaloneOutgoingWorkspace $workspace): Response
    {
        Gate::authorize('viewAny', StandaloneOutgoingDraft::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/standalone-outgoing/Index', [
            ...$workspace->index($user),
            'routes' => ['store' => route('back-office.standalone-outgoing.store')],
        ]);
    }

    public function show(Request $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, GetStandaloneOutgoingWorkspace $workspace): Response
    {
        Gate::authorize('view', $standaloneOutgoingDraft);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/standalone-outgoing/Show', $workspace->show($standaloneOutgoingDraft, $user));
    }

    public function store(
        StoreStandaloneOutgoingDraftRequest $request,
        CreateStandaloneOutgoingDraft $action,
        StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
    ): RedirectResponse {
        Gate::authorize('create', StandaloneOutgoingDraft::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $unitId = $this->unitId($data['organizational_unit_code']);
        abort_unless($assignmentResolver->hasStaffAssignmentForUnit($user, $unitId), 404);
        $draft = $action->execute(
            $user,
            $unitId,
            $this->templateVersionId($data['outgoing_letter_template_version_public_id'], $unitId),
            $this->attributes($data),
            $this->copyPositionIds($data['copy_position_codes'] ?? []),
            $request->file('document'),
        );

        return to_route('back-office.standalone-outgoing.show', $draft)->with('success', 'Konsep surat keluar berhasil disimpan.');
    }

    public function update(UpdateStandaloneOutgoingDraftRequest $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, UpdateStandaloneOutgoingDraft $action): RedirectResponse
    {
        Gate::authorize('edit', $standaloneOutgoingDraft);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $action->execute(
            $user,
            $standaloneOutgoingDraft,
            $this->templateVersionId($data['outgoing_letter_template_version_public_id'], $standaloneOutgoingDraft->organizational_unit_id),
            $this->attributes($data),
            $this->copyPositionIds($data['copy_position_codes'] ?? []),
        );

        return to_route('back-office.standalone-outgoing.show', $standaloneOutgoingDraft)->with('success', 'Data konsep surat diperbarui.');
    }

    public function storeDocumentVersion(StoreStandaloneOutgoingDocumentVersionRequest $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, AddStandaloneOutgoingDocumentVersion $action): RedirectResponse
    {
        Gate::authorize('edit', $standaloneOutgoingDraft);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $standaloneOutgoingDraft, $request->file('document'), $request->validated('revision_note'));

        return to_route('back-office.standalone-outgoing.show', $standaloneOutgoingDraft)->with('success', 'Versi PDF baru berhasil disimpan.');
    }

    public function submit(Request $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, SubmitStandaloneOutgoingDraft $action): RedirectResponse
    {
        Gate::authorize('edit', $standaloneOutgoingDraft);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $standaloneOutgoingDraft);

        return to_route('back-office.standalone-outgoing.show', $standaloneOutgoingDraft)->with('success', 'Konsep dikirim ke Kabag untuk diperiksa.');
    }

    public function reviewSection(ReviewStandaloneOutgoingDraftRequest $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, ReviewStandaloneOutgoingDraft $action): RedirectResponse
    {
        Gate::authorize('reviewSection', $standaloneOutgoingDraft);

        return $this->review($request, $standaloneOutgoingDraft, $action, StandaloneOutgoingReviewStage::SectionHead);
    }

    public function reviewAssistant(ReviewStandaloneOutgoingDraftRequest $request, StandaloneOutgoingDraft $standaloneOutgoingDraft, ReviewStandaloneOutgoingDraft $action): RedirectResponse
    {
        Gate::authorize('reviewAssistant', $standaloneOutgoingDraft);

        return $this->review($request, $standaloneOutgoingDraft, $action, StandaloneOutgoingReviewStage::Assistant);
    }

    private function review(ReviewStandaloneOutgoingDraftRequest $request, StandaloneOutgoingDraft $draft, ReviewStandaloneOutgoingDraft $action, StandaloneOutgoingReviewStage $stage): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $action->execute($user, $draft, $stage, StandaloneOutgoingReviewDecision::from($data['decision']), $data['reason'] ?? null);

        return to_route('back-office.standalone-outgoing.show', $draft)->with('success', 'Keputusan pemeriksaan berhasil disimpan.');
    }

    private function unitId(string $code): int
    {
        return (int) OrganizationalUnit::query()->where('code', $code)->where('is_active', true)->value('id');
    }

    private function templateVersionId(string $publicId, int $unitId): int
    {
        return (int) OutgoingLetterTemplateVersion::query()
            ->where('public_id', $publicId)
            ->whereHas('template', fn ($template) => $template->where('organizational_unit_id', $unitId))
            ->firstOrFail()
            ->getKey();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{recipient_name:string,recipient_organization:?string,recipient_position:?string,recipient_address:?string,recipient_email:?string,subject:string,summary:?string}
     */
    private function attributes(array $data): array
    {
        return [
            'recipient_name' => $data['recipient_name'],
            'recipient_organization' => $data['recipient_organization'] ?? null,
            'recipient_position' => $data['recipient_position'] ?? null,
            'recipient_address' => $data['recipient_address'] ?? null,
            'recipient_email' => $data['recipient_email'] ?? null,
            'subject' => $data['subject'],
            'summary' => $data['summary'] ?? null,
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<int>
     */
    private function copyPositionIds(array $codes): array
    {
        return array_values(Position::query()
            ->whereIn('code', $codes)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all());
    }
}
