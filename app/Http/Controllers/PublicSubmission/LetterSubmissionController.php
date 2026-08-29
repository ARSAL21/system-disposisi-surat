<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Actions\CreateOnlineSubmission;
use App\Actions\DeleteSubmissionDraft;
use App\Actions\UpdateSubmissionDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSubmission\ListLetterSubmissionRequest;
use App\Http\Requests\PublicSubmission\StoreLetterSubmissionRequest;
use App\Http\Requests\PublicSubmission\UpdateLetterSubmissionRequest;
use App\Http\Resources\LetterSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LetterSubmissionController extends Controller
{
    public function index(ListLetterSubmissionRequest $request): AnonymousResourceCollection|InertiaResponse
    {
        Gate::authorize('viewAny', LetterSubmission::class);

        /** @var User $user */
        $user = $request->user();
        $perPage = (int) $request->validated('per_page', 20);

        $submissions = LetterSubmission::query()
            ->ownedByPublicUser($user)
            ->with(['document', 'latestReview'])
            ->latest('created_at')
            ->paginate($perPage);

        $resource = LetterSubmissionResource::collection($submissions);

        if ($request->expectsJson()) {
            return $resource;
        }

        return Inertia::render('public/submissions/Index', [
            'submissions' => $resource->response()->getData(true),
        ]);
    }

    public function create(): InertiaResponse
    {
        Gate::authorize('create', LetterSubmission::class);

        return Inertia::render('public/submissions/Create');
    }

    public function store(
        StoreLetterSubmissionRequest $request,
        CreateOnlineSubmission $createOnlineSubmission,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('create', LetterSubmission::class);

        /** @var User $user */
        $user = $request->user();
        $submission = $createOnlineSubmission->execute($user, $request->validated());
        $submission->load(['document', 'latestReview']);

        if ($request->expectsJson()) {
            return (new LetterSubmissionResource($submission))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Draft submission berhasil dibuat.'),
        ]);

        return to_route('public.submissions.edit', $submission);
    }

    public function show(Request $request, LetterSubmission $submission): LetterSubmissionResource|InertiaResponse
    {
        Gate::authorize('view', $submission);

        $resource = new LetterSubmissionResource($submission->load(['document', 'latestReview']));

        if ($request->expectsJson()) {
            return $resource;
        }

        return Inertia::render('public/submissions/Show', [
            'submission' => $resource->resolve($request),
        ]);
    }

    public function edit(Request $request, LetterSubmission $submission): InertiaResponse
    {
        Gate::authorize('update', $submission);
        abort_unless($submission->isPubliclyEditable(), Response::HTTP_CONFLICT);

        return Inertia::render('public/submissions/Edit', [
            'submission' => (new LetterSubmissionResource($submission->load(['document', 'latestReview'])))->resolve($request),
        ]);
    }

    public function update(
        UpdateLetterSubmissionRequest $request,
        LetterSubmission $submission,
        UpdateSubmissionDraft $updateSubmissionDraft,
    ): LetterSubmissionResource|RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $submission = $updateSubmissionDraft->execute($user, $submission, $request->validated());

        $resource = new LetterSubmissionResource($submission->load(['document', 'latestReview']));

        if ($request->expectsJson()) {
            return $resource;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Metadata draft berhasil disimpan.'),
        ]);

        return to_route('public.submissions.edit', $submission);
    }

    public function destroy(
        Request $request,
        LetterSubmission $submission,
        DeleteSubmissionDraft $deleteSubmissionDraft,
    ): Response|RedirectResponse {
        Gate::authorize('delete', $submission);

        /** @var User $user */
        $user = $request->user();
        $deleteSubmissionDraft->execute($user, $submission);

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Draft submission telah dihapus.'),
        ]);

        return to_route('public.submissions.index');
    }
}
