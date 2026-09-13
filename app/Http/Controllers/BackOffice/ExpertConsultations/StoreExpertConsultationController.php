<?php

namespace App\Http\Controllers\BackOffice\ExpertConsultations;

use App\Actions\RequestExpertConsultations;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\ExpertConsultations\StoreExpertConsultationRequest;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreExpertConsultationController extends Controller
{
    public function __invoke(StoreExpertConsultationRequest $request, LetterRoute $letterRoute, RequestExpertConsultations $action): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $action->execute($actor, $letterRoute, $request->expertPositionIds(), $request->requestNote());

        return to_route('back-office.executive.inbox.show', $letterRoute)->with('success', 'Permintaan telaah Staf Ahli berhasil dikirim.');
    }
}
