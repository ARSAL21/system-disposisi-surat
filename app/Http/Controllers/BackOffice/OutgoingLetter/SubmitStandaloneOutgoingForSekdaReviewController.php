<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\SubmitStandaloneOutgoingForSekdaReview;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class SubmitStandaloneOutgoingForSekdaReviewController extends Controller
{
    public function __invoke(Request $request, OutgoingLetter $outgoingLetter, SubmitStandaloneOutgoingForSekdaReview $action): RedirectResponse
    {
        Gate::authorize('submitStandaloneToSekda', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Surat bernomor dikirim ke meja pengesahan Sekda.']);

        return back();
    }
}
