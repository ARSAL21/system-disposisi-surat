<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\ReturnStandaloneOutgoingFromSekda;
use App\Actions\ReviewStandaloneOutgoingManualSignatureScan;
use App\Enums\ManualSignatureReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ReturnStandaloneOutgoingForRevisionRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ReturnStandaloneOutgoingForRevisionController extends Controller
{
    public function __invoke(
        ReturnStandaloneOutgoingForRevisionRequest $request,
        OutgoingLetter $outgoingLetter,
        ReturnStandaloneOutgoingFromSekda $returnFromSekda,
        ReviewStandaloneOutgoingManualSignatureScan $reviewScan,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $note = $request->validated('note');

        if ($outgoingLetter->status === OutgoingLetterStatus::ManualScanReview) {
            Gate::authorize('reviewStandaloneManualScan', $outgoingLetter);
            $reviewScan->execute($user, $outgoingLetter, ManualSignatureReviewDecision::Returned, $note);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Scan dikembalikan ke unit asal untuk diperbaiki.']);
        } else {
            Gate::authorize('approveStandaloneBySekda', $outgoingLetter);
            $returnFromSekda->execute($user, $outgoingLetter, $note);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Surat dikembalikan ke unit asal untuk diperbaiki.']);
        }

        return back();
    }
}
