<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Actions\RouteIncomingLetter;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Routing\StoreLetterRouteRequest;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StoreLetterRouteController extends Controller
{
    public function __invoke(
        StoreLetterRouteRequest $request,
        IncomingLetter $incomingLetter,
        RouteIncomingLetter $routeIncomingLetter,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $routeIncomingLetter->execute(
            actor: $actor,
            incomingLetter: $incomingLetter,
            targetPositionId: $request->targetPositionId(),
        );

        return to_route('back-office.letter-routing.show', $incomingLetter)
            ->with('status', 'Surat berhasil diarahkan kepada pimpinan.');
    }
}
