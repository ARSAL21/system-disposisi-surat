<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LetterSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublicDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', LetterSubmission::class);

        /** @var User $user */
        $user = $request->user();
        $submissions = LetterSubmission::query()->ownedByPublicUser($user);

        $recentSubmissions = (clone $submissions)
            ->with('document')
            ->latest('created_at')
            ->limit(4)
            ->get();

        return Inertia::render('public/Dashboard', [
            'summary' => [
                'total' => (clone $submissions)->count(),
                'draft' => (clone $submissions)->where('status', SubmissionStatus::Draft)->count(),
                'submitted' => (clone $submissions)->where('status', SubmissionStatus::Submitted)->count(),
            ],
            'recentSubmissions' => LetterSubmissionResource::collection($recentSubmissions)->resolve($request),
        ]);
    }
}
