<?php

namespace App\Http\Requests\BackOffice\ExpertConsultations;

use App\Models\ExpertConsultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class CancelExpertConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('expertConsultation');
        abort_unless($consultation instanceof ExpertConsultation, 404);
        Gate::authorize('cancel', $consultation);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
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
