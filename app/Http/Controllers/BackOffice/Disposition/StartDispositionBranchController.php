<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\StartDispositionBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\StartDispositionBranchRequest;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StartDispositionBranchController extends Controller
{
    public function __invoke(
        StartDispositionBranchRequest $request,
        DispositionRecipient $dispositionRecipient,
        StartDispositionBranch $startBranch,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $startBranch->execute($actor, $dispositionRecipient);

        return to_route('back-office.dispositions.inbox.show', $dispositionRecipient)
            ->with('status', 'Penanganan cabang disposisi berhasil dimulai.');
    }
}
