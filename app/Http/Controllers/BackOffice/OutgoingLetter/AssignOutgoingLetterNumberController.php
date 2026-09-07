<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\AssignOutgoingLetterNumber;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\AssignOutgoingLetterNumberRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class AssignOutgoingLetterNumberController extends Controller
{
    public function __invoke(
        AssignOutgoingLetterNumberRequest $request,
        OutgoingLetter $outgoingLetter,
        AssignOutgoingLetterNumber $action,
    ): RedirectResponse {
        Gate::authorize('assignNumber', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validated();
        $action->execute($user, $outgoingLetter, $data['outgoing_number'], $data['letter_date']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nomor resmi surat keluar berhasil dicatat.']);

        return back();
    }
}
