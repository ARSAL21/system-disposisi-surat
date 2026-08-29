<?php

namespace App\Http\Requests\BackOffice\Intake;

use App\Models\LetterSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIntakeApprovalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAnyApproval', LetterSubmission::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tab' => ['sometimes', Rule::in(['pending', 'history'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
