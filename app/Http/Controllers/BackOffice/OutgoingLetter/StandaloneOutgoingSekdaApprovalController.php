<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\OutgoingLetters\StandaloneOutgoingSekdaApprovalPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class StandaloneOutgoingSekdaApprovalController extends Controller
{
    public function __invoke(Request $request, OutgoingLetter $outgoingLetter, StandaloneOutgoingSekdaApprovalPresenter $presenter): Response
    {
        Gate::authorize('view', $outgoingLetter);
        abort_unless($outgoingLetter->origin === OutgoingLetterOrigin::Standalone, 404);
        abort_unless(in_array($outgoingLetter->status, [
            OutgoingLetterStatus::SekdaReview,
            OutgoingLetterStatus::AwaitingManualSignature,
            OutgoingLetterStatus::ManualScanReview,
            OutgoingLetterStatus::ReadyForDelivery,
            OutgoingLetterStatus::RevisionRequired,
        ], true), 404);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/outgoing-letters/SekdaApproval', [
            'approval' => $presenter->present($outgoingLetter, $user),
        ]);
    }
}
