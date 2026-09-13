<?php

namespace App\Http\Requests\BackOffice\ExpertConsultations;

use App\Models\ExpertConsultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

final class ReportExpertConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('expertConsultation');
        abort_unless($consultation instanceof ExpertConsultation, 404);
        Gate::authorize('respond', $consultation);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'summary' => trim((string) $this->input('summary')),
            'recommendation' => trim((string) $this->input('recommendation')),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'min:10', 'max:4000'],
            'recommendation' => ['required', 'string', 'min:10', 'max:4000'],
            'document' => ['nullable', File::types(['pdf'])->max('20mb'), 'extensions:pdf'],
        ];
    }

    public function document(): ?UploadedFile
    {
        $file = $this->file('document');

        return $file instanceof UploadedFile ? $file : null;
    }
}
