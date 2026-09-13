<?php

namespace App\Http\Controllers\BackOffice\ExpertConsultations;

use App\Actions\CancelExpertConsultation;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\ExpertConsultations\CancelExpertConsultationRequest;
use App\Models\ExpertConsultation;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class CancelExpertConsultationController extends Controller
{
    public function __invoke(CancelExpertConsultationRequest $request, LetterRoute $letterRoute, ExpertConsultation $expertConsultation, CancelExpertConsultation $action): RedirectResponse
    {
        abort_unless((int) $expertConsultation->letter_route_id === (int) $letterRoute->getKey(), 404);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $action->execute($actor, $letterRoute, $expertConsultation, $request->reason());

        return to_route('back-office.executive.inbox.show', $letterRoute)->with('success', 'Permintaan telaah dibatalkan.');
    }
}
