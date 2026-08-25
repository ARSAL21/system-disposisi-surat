<?php

namespace App\Http\Requests\PublicSubmission;

use Illuminate\Foundation\Http\FormRequest;

abstract class SubmissionMetadataRequest extends FormRequest
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
            'sender_organization_name' => ['required', 'string', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'external_letter_number' => ['nullable', 'string', 'max:100'],
            'external_letter_date' => ['nullable', 'date', 'before_or_equal:today'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
