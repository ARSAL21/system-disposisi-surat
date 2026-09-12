<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\ChooseStandaloneOutgoingManualSignature;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ApproveStandaloneOutgoingRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ChooseStandaloneOutgoingManualSignatureController extends Controller
{
    public function __invoke(ApproveStandaloneOutgoingRequest $request, OutgoingLetter $outgoingLetter, ChooseStandaloneOutgoingManualSignature $action): RedirectResponse
    {
        Gate::authorize('approveStandaloneBySekda', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Surat kini menunggu tanda tangan fisik Sekda.']);

        return back();
    }
}
