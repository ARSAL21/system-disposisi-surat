<?php

namespace App\Http\Requests\PublicSubmission;

use App\Models\LetterSubmission;
use Illuminate\Support\Facades\Gate;

class UpdateLetterSubmissionRequest extends SubmissionMetadataRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        abort_unless($submission instanceof LetterSubmission, 404);
        Gate::authorize('update', $submission);

        return true;
    }
}
