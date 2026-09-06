<?php

namespace App\Http\Requests\BackOffice\LetterResponse;

use App\Models\LetterResponseDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AuthorizeLetterResponseMandateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dossier = $this->route('letterResponseDossier');
        abort_unless($dossier instanceof LetterResponseDossier, 404);
        Gate::authorize('authorizeResponse', $dossier);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => trim((string) $this->input('subject', '')),
            'signatory_position_code' => trim((string) $this->input('signatory_position_code', '')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'source_version_public_id' => ['required', 'string', 'ulid'],
            'signatory_position_code' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }
}
