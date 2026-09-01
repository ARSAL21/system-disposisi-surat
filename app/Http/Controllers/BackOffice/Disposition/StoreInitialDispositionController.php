<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\CreateInitialDisposition;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StoreInitialDispositionRequest;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreInitialDispositionController extends Controller
{
    public function __invoke(
        StoreInitialDispositionRequest $request,
        LetterRoute $letterRoute,
        CreateInitialDisposition $createDisposition,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $createDisposition->execute(
            actor: $actor,
            letterRoute: $letterRoute,
            recipientPositionId: $request->recipientPositionId(),
            instructionLabelIds: $request->instructionLabelIds(),
            instructionNote: $request->instructionNote(),
        );

        return to_route('back-office.executive.inbox.show', $letterRoute)
            ->with('status', 'Disposisi berhasil dikirim kepada Asisten.');
    }
}
