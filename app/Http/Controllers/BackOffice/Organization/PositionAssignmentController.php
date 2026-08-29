<?php

namespace App\Http\Controllers\BackOffice\Organization;

use App\Actions\AssignUserToPosition;
use App\Actions\BuildMutationSecurityState;
use App\Actions\EndPositionAssignment;
use App\Actions\GetPositionAssignmentWorkspace;
use App\Actions\ReplacePositionHolder;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Organization\AssignPositionRequest;
use App\Http\Requests\BackOffice\Organization\EndPositionAssignmentRequest;
use App\Http\Requests\BackOffice\Organization\ListPositionAssignmentsRequest;
use App\Http\Requests\BackOffice\Organization\ReplacePositionHolderRequest;
use App\Http\Resources\OrganizationPositionResource;
use App\Http\Resources\PositionAssignmentResource;
use App\Http\Resources\PositionLevelResource;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PositionAssignmentController extends Controller
{
    public function index(
        ListPositionAssignmentsRequest $request,
        GetPositionAssignmentWorkspace $getWorkspace,
        BuildMutationSecurityState $buildSecurityState,
    ): Response {
        $filters = [
            'search' => trim((string) $request->validated('search', '')),
            'status' => (string) $request->validated('status', 'all'),
            'position_level_id' => $request->validated('position_level_id'),
            'organizational_unit_id' => $request->validated('organizational_unit_id'),
            'selected_position' => $request->validated('selected_position'),
        ];
        $workspace = $getWorkspace->execute($filters);

        return Inertia::render('back-office/organization/assignments/Index', [
            'positions' => OrganizationPositionResource::collection($workspace['positions'])->response()->getData(true),
            'selectedPosition' => $workspace['selected_position'] === null
                ? null
                : OrganizationPositionResource::make($workspace['selected_position'])->resolve($request),
            'history' => $workspace['history'] === null
                ? null
                : PositionAssignmentResource::collection($workspace['history'])->response()->getData(true),
            'levels' => PositionLevelResource::collection($workspace['levels'])->resolve($request),
            'units' => $workspace['units']->map(fn ($unit): array => [
                'id' => $unit->getKey(),
                'name' => $unit->name,
            ])->values(),
            'users' => $workspace['users']->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
            ])->values(),
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'mutationSecurity' => $buildSecurityState->execute(
                $request,
                PermissionName::ManagePositionAssignments,
                route('back-office.organization.mutation.confirm', ['destination' => 'assignments']),
            ),
            'routes' => [
                'index' => route('back-office.organization.assignments.index'),
                'structure' => route('back-office.organization.structure.index'),
            ],
        ]);
    }

    public function store(
        AssignPositionRequest $request,
        Position $position,
        AssignUserToPosition $assign,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $assignee = User::query()->findOrFail($request->integer('user_id'));
        $assign->execute($actor, $assignee, $position);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pejabat berhasil ditugaskan.']);

        return back();
    }

    public function replace(
        ReplacePositionHolderRequest $request,
        Position $position,
        ReplacePositionHolder $replace,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $newHolder = User::query()->findOrFail($request->integer('user_id'));
        $replace->execute($actor, $newHolder, $position);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pejabat aktif berhasil diganti.']);

        return back();
    }

    public function end(
        EndPositionAssignmentRequest $request,
        PositionAssignment $positionAssignment,
        EndPositionAssignment $end,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $end->execute($actor, $positionAssignment);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Masa jabatan aktif berhasil diakhiri.']);

        return back();
    }
}
