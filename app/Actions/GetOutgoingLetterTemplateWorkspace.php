<?php

namespace App\Actions;

use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\Gate;

final class GetOutgoingLetterTemplateWorkspace
{
    public function __construct(private readonly StandaloneOutgoingPositionAssignmentResolver $resolver) {}

    /** @return array<string, mixed> */
    public function execute(User $user): array
    {
        $unitIds = array_values(array_unique([
            ...$this->resolver->staffUnitIds($user),
            ...$this->resolver->sectionHeadUnitIds($user),
        ]));

        return [
            'templates' => OutgoingLetterTemplate::query()
                ->with(['organizationalUnit', 'versions.uploadedBy'])
                ->whereIn('organizational_unit_id', $unitIds)
                ->orderBy('name')
                ->get()
                ->map(function (OutgoingLetterTemplate $template) use ($user): array {
                    $versions = $template->versions->sortByDesc('version_number');
                    $latest = $versions->first();

                    return [
                        'public_id' => $template->public_id,
                        'code' => $template->code,
                        'name' => $template->name,
                        'is_active' => $template->is_active,
                        'can_manage' => Gate::forUser($user)->allows('manage', $template),
                        'unit' => ['code' => $template->organizationalUnit->code, 'name' => $template->organizationalUnit->name],
                        'latest_version' => $latest === null ? null : $this->version($template, $latest),
                        'versions' => $versions->map(fn ($version): array => $this->version($template, $version))->values()->all(),
                        'links' => [
                            'version_store' => Gate::forUser($user)->allows('manage', $template)
                                ? route('back-office.outgoing-templates.versions.store', $template) : null,
                            'status_update' => Gate::forUser($user)->allows('manage', $template)
                                ? route('back-office.outgoing-templates.status.update', $template) : null,
                        ],
                    ];
                })->values()->all(),
            'units' => OrganizationalUnit::query()
                ->whereIn('id', $this->resolver->sectionHeadUnitIds($user))
                ->where('is_active', true)->orderBy('name')->get()
                ->map(fn ($unit): array => ['code' => $unit->code, 'name' => $unit->name])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function version(OutgoingLetterTemplate $template, OutgoingLetterTemplateVersion $version): array
    {
        return [
            'public_id' => $version->public_id,
            'version_number' => $version->version_number,
            'original_filename' => $version->original_filename,
            'size_bytes' => $version->size_bytes,
            'sha256' => $version->sha256,
            'qr_placement' => [
                'page_mode' => $version->qr_page_mode,
                'page_number' => $version->qr_page_number,
                'x_ratio' => $version->qr_x_ratio,
                'y_ratio' => $version->qr_y_ratio,
                'width_ratio' => $version->qr_width_ratio,
                'height_ratio' => $version->qr_height_ratio,
            ],
            'uploaded_by' => ['name' => $version->uploadedBy->name],
            'created_at' => $version->created_at->toISOString(),
            'links' => ['download' => route('back-office.outgoing-templates.versions.download', [$template, $version])],
        ];
    }
}
