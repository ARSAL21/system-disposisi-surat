<?php

namespace App\Http\Controllers\BackOffice\StandaloneOutgoing;

use App\Actions\ChangeOutgoingLetterTemplateStatus;
use App\Actions\CreateOutgoingLetterTemplate;
use App\Actions\CreateOutgoingLetterTemplateVersion;
use App\Actions\GetOutgoingLetterTemplateWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StandaloneOutgoing\StoreOutgoingLetterTemplateRequest;
use App\Http\Requests\BackOffice\StandaloneOutgoing\StoreOutgoingLetterTemplateVersionRequest;
use App\Http\Requests\BackOffice\StandaloneOutgoing\UpdateOutgoingLetterTemplateStatusRequest;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterTemplate;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OutgoingLetterTemplateController extends Controller
{
    public function index(Request $request, GetOutgoingLetterTemplateWorkspace $workspace): Response
    {
        Gate::authorize('viewAny', OutgoingLetterTemplate::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/outgoing-templates/Index', [
            ...$workspace->execute($user),
            'routes' => ['store' => route('back-office.outgoing-templates.store')],
        ]);
    }

    public function store(
        StoreOutgoingLetterTemplateRequest $request,
        CreateOutgoingLetterTemplate $action,
        StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
    ): RedirectResponse {
        Gate::authorize('create', OutgoingLetterTemplate::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $unit = OrganizationalUnit::query()->where('code', $data['organizational_unit_code'])->where('is_active', true)->firstOrFail();
        abort_unless($assignmentResolver->hasSectionHeadAssignmentForUnit($user, (int) $unit->getKey()), 404);

        $action->execute($user, (int) $unit->getKey(), $data['code'], $data['name'], $request->file('document'), $this->qr($data));

        return to_route('back-office.outgoing-templates.index')->with('success', 'Template surat keluar berhasil dibuat.');
    }

    public function storeVersion(
        StoreOutgoingLetterTemplateVersionRequest $request,
        OutgoingLetterTemplate $outgoingLetterTemplate,
        CreateOutgoingLetterTemplateVersion $action,
    ): RedirectResponse {
        Gate::authorize('manage', $outgoingLetterTemplate);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $action->execute($user, $outgoingLetterTemplate, $request->file('document'), $this->qr($data));

        return to_route('back-office.outgoing-templates.index')->with('success', 'Versi template baru berhasil disimpan.');
    }

    public function updateStatus(
        UpdateOutgoingLetterTemplateStatusRequest $request,
        OutgoingLetterTemplate $outgoingLetterTemplate,
        ChangeOutgoingLetterTemplateStatus $action,
    ): RedirectResponse {
        Gate::authorize('manage', $outgoingLetterTemplate);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetterTemplate, (bool) $request->validated('is_active'));

        return to_route('back-office.outgoing-templates.index')->with('success', 'Status template berhasil diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{qr_page_mode:string,qr_page_number:int|null,qr_x_ratio:float,qr_y_ratio:float,qr_width_ratio:float,qr_height_ratio:float}
     */
    private function qr(array $data): array
    {
        return [
            'qr_page_mode' => $data['qr_page_mode'],
            'qr_page_number' => isset($data['qr_page_number']) ? (int) $data['qr_page_number'] : null,
            'qr_x_ratio' => (float) $data['qr_x_ratio'],
            'qr_y_ratio' => (float) $data['qr_y_ratio'],
            'qr_width_ratio' => (float) $data['qr_width_ratio'],
            'qr_height_ratio' => (float) $data['qr_height_ratio'],
        ];
    }
}
