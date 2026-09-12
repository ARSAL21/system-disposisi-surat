<?php

namespace App\Http\Controllers\BackOffice\StandaloneOutgoing;

use App\Actions\AssignStandaloneOutgoingNumber;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\AssignOutgoingLetterNumberRequest;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class AssignStandaloneOutgoingNumberController extends Controller
{
    public function __invoke(
        AssignOutgoingLetterNumberRequest $request,
        StandaloneOutgoingDraft $standaloneOutgoingDraft,
        AssignStandaloneOutgoingNumber $action,
    ): RedirectResponse {
        Gate::authorize('assignNumber', $standaloneOutgoingDraft);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $action->execute($user, $standaloneOutgoingDraft, $data['outgoing_number'], $data['letter_date']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nomor resmi dicatat. Surat siap diajukan ke Sekda.']);

        return back();
    }
}
