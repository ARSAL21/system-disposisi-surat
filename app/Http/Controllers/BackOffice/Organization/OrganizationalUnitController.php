<?php

namespace App\Http\Controllers\BackOffice\Organization;

use App\Actions\ChangeOrganizationalUnitStatus;
use App\Actions\CreateOrganizationalUnit;
use App\Actions\UpdateOrganizationalUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Organization\ChangeOrganizationalUnitStatusRequest;
use App\Http\Requests\BackOffice\Organization\StoreOrganizationalUnitRequest;
use App\Http\Requests\BackOffice\Organization\UpdateOrganizationalUnitRequest;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OrganizationalUnitController extends Controller
{
    public function store(StoreOrganizationalUnitRequest $request, CreateOrganizationalUnit $create): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $unit = $create->execute($actor, $request->validatedPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Unit {$unit->name} berhasil dibuat."]);

        return back();
    }

    public function update(
        UpdateOrganizationalUnitRequest $request,
        OrganizationalUnit $organizationalUnit,
        UpdateOrganizationalUnit $update,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $update->execute($actor, $organizationalUnit, $request->validatedPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit organisasi berhasil diperbarui.']);

        return back();
    }

    public function status(
        ChangeOrganizationalUnitStatusRequest $request,
        OrganizationalUnit $organizationalUnit,
        ChangeOrganizationalUnitStatus $changeStatus,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $unit = $changeStatus->execute($actor, $organizationalUnit, $request->boolean('is_active'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $unit->is_active ? 'Unit organisasi diaktifkan.' : 'Unit organisasi dinonaktifkan.',
        ]);

        return back();
    }
}
