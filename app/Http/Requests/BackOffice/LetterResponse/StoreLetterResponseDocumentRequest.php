<?php

namespace App\Http\Requests\BackOffice\LetterResponse;

use App\Models\DispositionRecipient;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

class StoreLetterResponseDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dossier = $this->dossier();
        $routeName = $this->route()?->getName();

        if (in_array($routeName, [
            'back-office.letter-responses.materials.store',
            'back-office.letter-responses.proposals.store',
        ], true)) {
            $recipient = $this->route('dispositionRecipient');
            abort_unless($recipient instanceof DispositionRecipient, 404);
            abort_unless($recipient->disposition()
                ->where('incoming_letter_id', $dossier->incoming_letter_id)
                ->exists(), 404);
            Gate::authorize('contributeForPosition', [$dossier, $recipient->recipient_position_id]);

            return true;
        }

        if ($routeName === 'back-office.letter-responses.documents.versions.store') {
            $document = $this->route('letterResponseDocument');
            abort_unless($document instanceof LetterResponseDocument, 404);
            abort_unless((int) $document->letter_response_dossier_id === (int) $dossier->getKey(), 404);
            Gate::authorize('contributeForPosition', [$dossier, $document->owner_position_id]);

            return true;
        }

        if ($routeName === 'back-office.letter-responses.consolidations.store') {
            $positionId = (int) $dossier->incomingLetter->routes()->orderBy('id')->value('recipient_position_id');
            abort_unless($positionId > 0, 404);
            Gate::authorize('contributeForPosition', [$dossier, $positionId]);

            return true;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('revision_note')) {
            $this->merge(['revision_note' => trim((string) $this->input('revision_note'))]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'document' => ['required', File::types(['pdf'])->max('20mb'), 'extensions:pdf'],
            'revision_note' => ['required', 'string', 'min:10', 'max:2000'],
            'source_version_public_ids' => ['sometimes', 'array', 'max:20'],
            'source_version_public_ids.*' => ['required', 'string', 'ulid', 'distinct'],
        ];
    }

    public function dossier(): LetterResponseDossier
    {
        $dossier = $this->route('letterResponseDossier');
        abort_unless($dossier instanceof LetterResponseDossier, 404);

        return $dossier;
    }

    public function document(): UploadedFile
    {
        $file = $this->file('document');
        abort_unless($file instanceof UploadedFile, 422);

        return $file;
    }

    public function revisionNote(): string
    {
        return (string) $this->validated('revision_note');
    }

    /** @return list<string> */
    public function sourceVersionPublicIds(): array
    {
        $ids = $this->validated('source_version_public_ids', []);

        return is_array($ids) ? array_values($ids) : [];
    }
}
