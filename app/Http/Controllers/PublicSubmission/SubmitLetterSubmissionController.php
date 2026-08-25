<?php

namespace App\Http\Controllers\PublicSubmission;

use App\Actions\SubmitLetterSubmission;
use App\Http\Controllers\Controller;
use App\Http\Resources\LetterSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SubmitLetterSubmissionController extends Controller
{
    public function __invoke(
        Request $request,
        LetterSubmission $submission,
        SubmitLetterSubmission $submitLetterSubmission,
    ): LetterSubmissionResource|RedirectResponse {
        Gate::authorize('submit', $submission);

        /** @var User $user */
        $user = $request->user();
        $submission = $submitLetterSubmission->execute($user, $submission);

        $resource = new LetterSubmissionResource($submission->load('document'));

        if ($request->expectsJson()) {
            return $resource;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Submission berhasil dikirim dan kini menunggu pemeriksaan Bagian Umum.'),
        ]);

        return to_route('public.submissions.show', $submission);
    }
}
