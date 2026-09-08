<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\GetLetterResponseWorkspace;
use App\Http\Controllers\Controller;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class LetterResponseController extends Controller
{
    public function index(Request $request, GetLetterResponseWorkspace $workspace): Response
    {
        Gate::authorize('viewAny', LetterResponseDossier::class);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/letter-responses/Index', [
            'dossiers' => $workspace->index($user),
            'routes' => ['index' => route('back-office.letter-responses.index')],
        ]);
    }

    public function show(
        Request $request,
        LetterResponseDossier $letterResponseDossier,
        GetLetterResponseWorkspace $workspace,
    ): Response {
        Gate::authorize('view', $letterResponseDossier);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('back-office/letter-responses/Show', [
            'dossier' => $workspace->show($letterResponseDossier, $user),
            'routes' => ['index' => route('back-office.letter-responses.index')],
        ]);
    }
}
