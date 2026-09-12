<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\ForwardSekdaDispositionToAssistants;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StoreSekdaDispositionRequest;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreSekdaDispositionController extends Controller
{
    public function __invoke(
        StoreSekdaDispositionRequest $request,
        DispositionRecipient $dispositionRecipient,
        ForwardSekdaDispositionToAssistants $forwardDisposition,
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

        return to_route('back-office.executive.inbox.recipient.show', $dispositionRecipient)
            ->with('status', 'Disposisi berhasil diteruskan kepada Asisten terpilih.');
    }
}
