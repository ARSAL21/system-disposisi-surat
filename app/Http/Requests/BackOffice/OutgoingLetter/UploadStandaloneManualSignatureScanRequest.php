<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use Illuminate\Foundation\Http\FormRequest;

final class UploadStandaloneManualSignatureScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'manual_scan' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:20480'],
        ];
    }
}
