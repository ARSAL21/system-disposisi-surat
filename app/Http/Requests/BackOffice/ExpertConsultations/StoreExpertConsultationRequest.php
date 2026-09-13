<?php

namespace App\Http\Requests\BackOffice\ExpertConsultations;

use App\Models\LetterRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreExpertConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $route = $this->route('letterRoute');
        abort_unless($route instanceof LetterRoute, 404);
        Gate::authorize('requestExpertConsultations', $route);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['request_note' => trim((string) $this->input('request_note', ''))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'expert_position_ids' => ['required', 'array', 'min:1', 'max:3'],
            'expert_position_ids.*' => ['required', 'integer', 'distinct', Rule::exists('positions', 'id')],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return list<int> */
    public function expertPositionIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('expert_position_ids')));
    }

    public function requestNote(): ?string
    {
        $note = (string) $this->validated('request_note', '');

        return $note === '' ? null : $note;
    }
}
