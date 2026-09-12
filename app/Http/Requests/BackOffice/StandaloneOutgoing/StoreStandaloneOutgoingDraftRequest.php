<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStandaloneOutgoingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'organizational_unit_code' => ['required', 'string', 'max:80', Rule::exists('organizational_units', 'code')->where('is_active', true)],
            'outgoing_letter_template_version_public_id' => ['required', 'ulid', Rule::exists('outgoing_letter_template_versions', 'public_id')],
            ...$this->contentRules(),
            'copy_position_codes' => ['nullable', 'array', 'max:12'],
            'copy_position_codes.*' => ['string', 'distinct', 'max:100', Rule::exists('positions', 'code')],
            'document' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    protected function contentRules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'min:2', 'max:150'],
            'recipient_organization' => ['nullable', 'string', 'max:180'],
            'recipient_position' => ['nullable', 'string', 'max:150'],
            'recipient_address' => ['nullable', 'string', 'max:2000'],
            'recipient_email' => ['nullable', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'min:5', 'max:500'],
            'summary' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
