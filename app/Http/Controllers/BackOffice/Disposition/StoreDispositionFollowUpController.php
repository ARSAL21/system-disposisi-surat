<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\AddDispositionFollowUp;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StoreDispositionFollowUpRequest;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreDispositionFollowUpController extends Controller
{
    public function __invoke(
        StoreDispositionFollowUpRequest $request,
        DispositionRecipient $dispositionRecipient,
        AddDispositionFollowUp $addFollowUp,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $addFollowUp->execute($actor, $dispositionRecipient, $request->note());

        return to_route('back-office.dispositions.inbox.show', $dispositionRecipient)
            ->with('status', 'Catatan tindak lanjut berhasil ditambahkan.');
    }
}
