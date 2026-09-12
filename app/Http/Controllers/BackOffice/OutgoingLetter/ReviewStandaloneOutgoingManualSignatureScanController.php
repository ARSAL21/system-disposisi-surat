<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\ReviewStandaloneOutgoingManualSignatureScan;
use App\Enums\ManualSignatureReviewDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ReviewStandaloneManualSignatureScanRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ReviewStandaloneOutgoingManualSignatureScanController extends Controller
{
    public function __invoke(ReviewStandaloneManualSignatureScanRequest $request, OutgoingLetter $outgoingLetter, ReviewStandaloneOutgoingManualSignatureScan $action): RedirectResponse
    {
        Gate::authorize('reviewStandaloneManualScan', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter, ManualSignatureReviewDecision::Verified, $request->validated('note'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Scan dinyatakan sesuai. Surat siap dikirim.']);

        return back();
    }
}
