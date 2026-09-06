<?php

namespace App\Http\Requests\BackOffice\LetterResponse;

use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDossier;
use App\Services\LetterResponseReviewerPositionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReturnLetterResponseDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dossier = $this->route('letterResponseDossier');
        $document = $this->route('letterResponseDocument');
        abort_unless($dossier instanceof LetterResponseDossier, 404);
        abort_unless($document instanceof LetterResponseDocument, 404);
        abort_unless((int) $document->letter_response_dossier_id === (int) $dossier->getKey(), 404);
        $reviewerPositionId = app(LetterResponseReviewerPositionResolver::class)->resolve($dossier, $document);
        Gate::authorize('reviewForPosition', [$dossier, $reviewerPositionId]);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason', ''))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:2000']];
    }

    public function reason(): string
    {
        return (string) $this->validated('reason');
    }
}
