<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Actions\CompleteDispositionBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\CompleteDispositionBranchRequest;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CompleteDispositionBranchController extends Controller
{
    public function __invoke(
        CompleteDispositionBranchRequest $request,
        DispositionRecipient $dispositionRecipient,
        CompleteDispositionBranch $completeBranch,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $completeBranch->execute(
            $actor,
            $dispositionRecipient,
            $request->completionNote(),
        );

        return to_route('back-office.dispositions.inbox.show', $dispositionRecipient)
            ->with('status', 'Cabang disposisi berhasil diselesaikan.');
    }
}
