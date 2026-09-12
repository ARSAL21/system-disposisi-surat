<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOutgoingLetterTemplateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
