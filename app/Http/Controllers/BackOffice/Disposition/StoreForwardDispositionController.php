<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\ForwardDisposition;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StoreForwardDispositionRequest;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreForwardDispositionController extends Controller
{
    public function __invoke(
        StoreForwardDispositionRequest $request,
        DispositionRecipient $dispositionRecipient,
        ForwardDisposition $forwardDisposition,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $forwardDisposition->execute(
            actor: $actor,
            parentRecipient: $dispositionRecipient,
            recipientPositionIds: $request->recipientPositionIds(),
            instructionLabelIds: $request->instructionLabelIds(),
            instructionNote: $request->instructionNote(),
        );

        return to_route('back-office.dispositions.inbox.show', $dispositionRecipient)
            ->with('status', 'Disposisi berhasil diteruskan kepada Kepala Bagian.');
    }
}
