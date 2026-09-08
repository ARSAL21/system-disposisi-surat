<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\GetOutgoingLetterRegister;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ListOutgoingLettersRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\OutgoingLetters\OutgoingLetterPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OutgoingLetterController extends Controller
{
    public function index(ListOutgoingLettersRequest $request, GetOutgoingLetterRegister $register): Response
    {
        Gate::authorize('viewAny', OutgoingLetter::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $filters = $request->validated();
        $payload = $register->execute($user, $filters);

        return Inertia::render('back-office/outgoing-letters/Index', [
            ...$payload,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'source' => (string) ($filters['source'] ?? ''),
                'year' => isset($filters['year']) ? (string) $filters['year'] : '',
            ],
            'routes' => ['index' => route('back-office.outgoing-letters.index')],
        ]);
    }

    public function show(Request $request, OutgoingLetter $outgoingLetter, OutgoingLetterPresenter $presenter): Response
    {
        Gate::authorize('view', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/outgoing-letters/Show', [
            'outgoingLetter' => $presenter->detail($outgoingLetter, $user),
        ]);
    }
}
