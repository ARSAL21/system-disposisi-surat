<?php

namespace App\Http\Controllers\BackOffice\Audit;

use App\Actions\GetLetterActivityWorkspace;
use App\Auditing\LetterActivityCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Audit\ListLetterActivitiesRequest;
use App\Models\User;
use App\Services\LetterActivityVisibilityResolver;
use Inertia\Inertia;
use Inertia\Response;

class LetterActivityController extends Controller
{
    public function __invoke(
        ListLetterActivitiesRequest $request,
        LetterActivityVisibilityResolver $visibilityResolver,
        GetLetterActivityWorkspace $getWorkspace,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $visibility = $visibilityResolver->resolve($user);
        $filters = $request->filters($visibility);
        $workspace = $getWorkspace->execute($filters, $visibility);

        return Inertia::render('back-office/letter-activities/Index', [
            'activities' => $workspace['activities'],
            'filters' => $filters,
            'filterOptions' => LetterActivityCatalog::filterOptions($workspace['actors']),
            'summary' => $workspace['summary'],
            'routes' => [
                'index' => route('back-office.letter-activities.index'),
            ],
            'visibility' => $visibility->value,
            'timezone' => config('letter-activity.timezone'),
            'today' => now((string) config('letter-activity.timezone'))->toDateString(),
            'preview' => false,
        ]);
    }
}
