<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\CreateMayorDispositionToSekda;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StoreMayorDispositionRequest;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreMayorDispositionController extends Controller
{
    public function __invoke(
        StoreMayorDispositionRequest $request,
        LetterRoute $letterRoute,
        CreateMayorDispositionToSekda $createDisposition,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $createDisposition->execute(
            actor: $actor,
            letterRoute: $letterRoute,
            instructionLabelIds: $request->instructionLabelIds(),
            instructionNote: $request->instructionNote(),
        );

        return to_route('back-office.executive.inbox.show', $letterRoute)
            ->with('status', 'Arahan formal berhasil diteruskan kepada Sekda.');
    }
}
