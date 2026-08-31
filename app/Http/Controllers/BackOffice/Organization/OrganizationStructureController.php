<?php

namespace App\Http\Controllers\BackOffice\Organization;

use App\Actions\BuildMutationSecurityState;
use App\Actions\GetOrganizationStructureWorkspace;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Organization\ListOrganizationStructureRequest;
use App\Http\Resources\OrganizationalUnitResource;
use App\Http\Resources\OrganizationPositionResource;
use App\Http\Resources\PositionLevelResource;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationStructureController extends Controller
{
    public function __invoke(
        ListOrganizationStructureRequest $request,
        GetOrganizationStructureWorkspace $getWorkspace,
        BuildMutationSecurityState $buildSecurityState,
    ): Response {
        $filters = [
            'section' => (string) $request->validated('section', 'levels'),
            'search' => trim((string) $request->validated('search', '')),
            'status' => (string) $request->validated('status', 'all'),
            'position_level_id' => $request->validated('position_level_id'),
            'organizational_unit_id' => $request->validated('organizational_unit_id'),
        ];
        $workspace = $getWorkspace->execute($filters);

        return Inertia::render('back-office/organization/structure/Index', [
            'levels' => PositionLevelResource::collection($workspace['levels'])->resolve($request),
            'units' => $workspace['units'] === null
                ? null
                : OrganizationalUnitResource::collection($workspace['units'])->response()->getData(true),
            'positions' => $workspace['positions'] === null
                ? null
                : OrganizationPositionResource::collection($workspace['positions'])->response()->getData(true),
            'tree' => $workspace['tree'],
            'unitOptions' => $workspace['unit_options']->map(fn ($unit): array => [
                'id' => $unit->getKey(),
                'parent_id' => $unit->parent_id,
                'code' => $unit->code,
                'name' => $unit->name,
            ])->values(),
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'mutationSecurity' => $buildSecurityState->execute(
                $request,
                PermissionName::ManageOrganization,
                route('back-office.organization.mutation.confirm', ['destination' => 'structure']),
            ),
            'routes' => [
                'index' => route('back-office.organization.structure.index'),
                'store_unit' => route('back-office.organization.units.store'),
                'store_position' => route('back-office.organization.positions.store'),
                'assignments' => route('back-office.organization.assignments.index'),
            ],
        ]);
    }
}
