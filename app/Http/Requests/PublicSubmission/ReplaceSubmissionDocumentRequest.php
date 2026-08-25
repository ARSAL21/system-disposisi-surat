<?php

namespace App\Http\Requests\PublicSubmission;

use App\Models\LetterSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

class ReplaceSubmissionDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        abort_unless($submission instanceof LetterSubmission, 404);
        Gate::authorize('replaceDocument', $submission);

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                File::types(['pdf'])->max('20mb'),
                'extensions:pdf',
            ],
        ];
    }
}
