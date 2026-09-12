<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewStandaloneOutgoingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['APPROVED', 'RETURNED'])],
            'reason' => ['nullable', 'string', 'min:10', 'max:2000', 'required_if:decision,RETURNED'],
        ];
    }
}
