<?php

namespace App\Http\Controllers\BackOffice\IncomingRegister;

use App\Actions\GetIncomingRegisterWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\IncomingRegister\ListIncomingRegisterRequest;
use App\Http\Resources\BackOffice\IncomingRegisterResource;
use App\Models\IncomingLetter;
use App\Models\LetterSubmission;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IncomingRegisterController extends Controller
{
    public function __invoke(
        ListIncomingRegisterRequest $request,
        GetIncomingRegisterWorkspace $getWorkspace,
    ): Response {
        Gate::authorize('viewAnyRegister', IncomingLetter::class);

        $filters = $request->filters();
        $workspace = $getWorkspace->execute($filters);
        $letters = $workspace['letters'];

        return Inertia::render('back-office/incoming-letters/Index', [
            'letters' => [
                'data' => IncomingRegisterResource::collection($letters->getCollection())->resolve($request),
                'pagination' => [
                    'current_page' => $letters->currentPage(),
                    'last_page' => $letters->lastPage(),
                    'from' => $letters->firstItem() ?? 0,
                    'to' => $letters->lastItem() ?? 0,
                    'total' => $letters->total(),
                    'previous_url' => $letters->previousPageUrl(),
                    'next_url' => $letters->nextPageUrl(),
                ],
            ],
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.incoming-letters.index'),
                'create_manual' => Gate::allows('createManual', LetterSubmission::class)
                    ? route('back-office.intake.manual.create')
                    : null,
            ],
        ]);
    }
}
