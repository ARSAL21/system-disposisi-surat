<?php

namespace App\Http\Requests\PublicSubmission;

use Illuminate\Foundation\Http\FormRequest;

class ListLetterSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
