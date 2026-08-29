<?php

namespace App\Http\Requests\BackOffice\Intake;

use App\Enums\SubmissionReviewOutcome;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class ScreenSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        abort_unless($submission instanceof LetterSubmission, 404);
        Gate::authorize('screenIntake', $submission);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'outcome' => ['required', new Enum(SubmissionReviewOutcome::class)],
            'checklist' => ['required', 'array', 'size:'.count(SubmissionScreeningChecklist::keys())],
            'checklist.*.id' => [
                'required',
                'string',
                'distinct',
                Rule::in(SubmissionScreeningChecklist::keys()),
            ],
            'checklist.*.checked' => ['required', 'boolean'],
            'note' => [
                'nullable',
                'string',
                'min:10',
                'max:2000',
                Rule::requiredIf($this->input('outcome') === SubmissionReviewOutcome::RevisionRequired->value),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('checklist')) {
                return;
            }

            /** @var list<array{id: string, checked: bool}> $items */
            $items = $this->input('checklist', []);
            $submittedKeys = array_column($items, 'id');
            $expectedKeys = SubmissionScreeningChecklist::keys();
            sort($submittedKeys);
            sort($expectedKeys);

            if ($submittedKeys !== $expectedKeys) {
                $validator->errors()->add('checklist', 'Checklist screening must contain every official item exactly once.');

                return;
            }

            if ($this->input('outcome') === SubmissionReviewOutcome::ReadyForApproval->value
                && ! SubmissionScreeningChecklist::isComplete(
                    SubmissionScreeningChecklist::normalize($items),
                )) {
                $validator->errors()->add('checklist', 'Every checklist item must be complete before approval review.');
            }
        }];
    }
}
