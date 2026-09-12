<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\ApproveStandaloneOutgoingWithQr;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ApproveStandaloneOutgoingRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ApproveStandaloneOutgoingWithQrController extends Controller
{
    public function __invoke(ApproveStandaloneOutgoingRequest $request, OutgoingLetter $outgoingLetter, ApproveStandaloneOutgoingWithQr $action): RedirectResponse
    {
        Gate::authorize('approveStandaloneBySekda', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'PDF disahkan Sekda dengan QR verifikasi dan siap dikirim.']);

        return back();
    }
}
