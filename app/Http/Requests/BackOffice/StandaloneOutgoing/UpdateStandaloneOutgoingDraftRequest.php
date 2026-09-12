<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Validation\Rule;

final class UpdateStandaloneOutgoingDraftRequest extends StoreStandaloneOutgoingDraftRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'outgoing_letter_template_version_public_id' => ['required', 'ulid', Rule::exists('outgoing_letter_template_versions', 'public_id')],
            ...$this->contentRules(),
            'copy_position_codes' => ['nullable', 'array', 'max:12'],
            'copy_position_codes.*' => ['string', 'distinct', 'max:100', Rule::exists('positions', 'code')],
        ];
    }
}
