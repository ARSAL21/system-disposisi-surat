<?php

namespace App\Http\Requests\BackOffice\StandaloneOutgoing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOutgoingLetterTemplateVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:docx', 'max:10240'],
            'qr_page_mode' => ['required', Rule::in(['LAST_PAGE', 'SPECIFIC_PAGE'])],
            'qr_page_number' => ['nullable', 'integer', 'min:1', 'max:999', 'required_if:qr_page_mode,SPECIFIC_PAGE'],
            'qr_x_ratio' => ['required', 'numeric', 'between:0,1'],
            'qr_y_ratio' => ['required', 'numeric', 'between:0,1'],
            'qr_width_ratio' => ['required', 'numeric', 'gt:0', 'lte:1'],
            'qr_height_ratio' => ['required', 'numeric', 'gt:0', 'lte:1'],
        ];
    }
}
