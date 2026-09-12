<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOutgoingLetterTemplateRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'document' => ['required', 'file', 'mimes:docx', 'max:10240'],
            ...$this->qrRules(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function qrRules(): array
    {
        return [
            'qr_page_mode' => ['required', Rule::in(['LAST_PAGE', 'SPECIFIC_PAGE'])],
            'qr_page_number' => ['nullable', 'integer', 'min:1', 'max:999', 'required_if:qr_page_mode,SPECIFIC_PAGE'],
            'qr_x_ratio' => ['required', 'numeric', 'between:0,1'],
            'qr_y_ratio' => ['required', 'numeric', 'between:0,1'],
            'qr_width_ratio' => ['required', 'numeric', 'gt:0', 'lte:1'],
            'qr_height_ratio' => ['required', 'numeric', 'gt:0', 'lte:1'],
        ];
    }
}
