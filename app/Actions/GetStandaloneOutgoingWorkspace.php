<?php

namespace App\Actions;

use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterTemplate;
use App\Models\Position;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use App\StandaloneOutgoing\StandaloneOutgoingPresenter;
use App\StandaloneOutgoing\StandaloneOutgoingScopeQuery;

final class GetStandaloneOutgoingWorkspace
{
    public function __construct(
        private readonly StandaloneOutgoingScopeQuery $scopeQuery,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly StandaloneOutgoingPresenter $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function index(User $user): array
    {
        $drafts = $this->scopeQuery->visibleTo($user)
            ->with($this->draftRelations())
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->through(
                fn (StandaloneOutgoingDraft $draft): array => $this->presenter->listItem($draft, $user),
            );

        return [
            'drafts' => $drafts,
            'form_options' => $this->formOptions($user),
        ];
    }

    /** @return array<string, mixed> */
    public function show(StandaloneOutgoingDraft $draft, User $user): array
    {
        $draft->load($this->draftRelations());

        return [
            'draft' => $this->presenter->detail($draft, $user),
            'form_options' => $this->formOptions($user),
        ];
    }

    /** @return array<string, mixed> */
    private function formOptions(User $user): array
    {
        $unitIds = $this->assignmentResolver->staffUnitIds($user);
        $units = OrganizationalUnit::query()->whereIn('id', $unitIds)->where('is_active', true)->orderBy('name')->get();
        $templates = OutgoingLetterTemplate::query()
            ->with(['versions', 'organizationalUnit'])
            ->whereIn('organizational_unit_id', $unitIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (OutgoingLetterTemplate $template): array => [
                'unit_code' => $template->organizationalUnit->code,
                'code' => $template->code,
                'name' => $template->name,
                'latest_version' => ($version = $template->versions->sortByDesc('version_number')->first()) === null ? null : [
                    'public_id' => $version->public_id,
                    'version_number' => $version->version_number,
                ],
            ])->filter(fn (array $template): bool => $template['latest_version'] !== null)->values()->all();

        $positions = Position::query()
            ->with('organizationalUnit')
            ->where('is_active', true)
            ->whereHas('positionLevel', fn ($level) => $level->where('is_active', true)->whereIn('code', [
                OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
                OrganizationCatalog::ASSISTANT_LEVEL,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
            ]))
            ->orderBy('name')
            ->get()
            ->map(fn (Position $position): array => [
                'code' => $position->code,
                'name' => $position->name,
                'unit_name' => $position->organizationalUnit?->name,
            ])->values()->all();

        return [
            'units' => $units->map(fn ($unit): array => ['code' => $unit->code, 'name' => $unit->name])->values()->all(),
            'templates' => $templates,
            'copy_positions' => $positions,
        ];
    }

    /** @return array<int, string|array<int, string>> */
    private function draftRelations(): array
    {
        return [
            'organizationalUnit', 'templateVersion.template', 'createdBy', 'currentDocumentVersion',
            'copyRecipients.position.organizationalUnit', 'documentVersions.uploadedBy', 'reviews.decidedBy',
        ];
    }
}
