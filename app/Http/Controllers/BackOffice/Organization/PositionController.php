<?php

namespace App\Http\Controllers\BackOffice\Organization;

use App\Actions\ChangePositionStatus;
use App\Actions\CreatePosition;
use App\Actions\UpdatePosition;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Organization\ChangePositionStatusRequest;
use App\Http\Requests\BackOffice\Organization\StorePositionRequest;
use App\Http\Requests\BackOffice\Organization\UpdatePositionRequest;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function store(StorePositionRequest $request, CreatePosition $create): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $position = $create->execute($actor, $request->validatedPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Jabatan {$position->name} berhasil dibuat."]);

        return back();
    }

    public function update(
        UpdatePositionRequest $request,
        Position $position,
        UpdatePosition $update,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $update->execute($actor, $position, $request->validatedPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jabatan berhasil diperbarui.']);

        return back();
    }

    public function status(
        ChangePositionStatusRequest $request,
        Position $position,
        ChangePositionStatus $changeStatus,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $position = $changeStatus->execute($actor, $position, $request->boolean('is_active'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $position->is_active ? 'Jabatan diaktifkan.' : 'Jabatan dinonaktifkan.',
        ]);

        return back();
    }
}
