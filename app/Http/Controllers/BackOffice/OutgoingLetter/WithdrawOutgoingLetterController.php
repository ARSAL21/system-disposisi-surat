<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\WithdrawOutgoingLetterMandate;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\WithdrawOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class WithdrawOutgoingLetterController extends Controller
{
    public function __invoke(
        WithdrawOutgoingLetterRequest $request,
        OutgoingLetter $outgoingLetter,
        WithdrawOutgoingLetterMandate $action,
    ): RedirectResponse {
        Gate::authorize('withdraw', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter, $request->string('withdrawal_reason')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mandat ditarik dan tetap tersimpan dalam histori.']);

        return back();
    }
}
