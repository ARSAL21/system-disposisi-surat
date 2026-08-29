<?php

namespace App\Http\Requests\BackOffice\Intake;

use App\Enums\SubmissionDecisionOutcome;
use App\Models\LetterSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class DecideSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        abort_unless($submission instanceof LetterSubmission, 404);
        Gate::authorize('decideIntake', $submission);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $sender = $this->input('sender_organization');

        $this->merge([
            'agenda_number' => trim((string) $this->input('agenda_number', '')),
            'note' => ($note = trim((string) $this->input('note', ''))) !== '' ? $note : null,
            'sender_organization' => is_array($sender)
                ? array_map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $sender)
                : $sender,
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $outcome = $this->input('outcome');
        $registering = $outcome === SubmissionDecisionOutcome::Registered->value;
        $requiresNote = in_array($outcome, [
            SubmissionDecisionOutcome::InternalRevisionRequired->value,
            SubmissionDecisionOutcome::Rejected->value,
        ], true);
        $senderMode = $this->input('sender_organization.mode');

        return [
            'outcome' => ['required', new Enum(SubmissionDecisionOutcome::class)],
            'note' => [
                Rule::requiredIf($requiresNote),
                'nullable',
                'string',
                'min:10',
                'max:2000',
            ],
            'agenda_number' => [
                Rule::requiredIf($registering),
                Rule::prohibitedIf(! $registering),
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9.\/-]*$/',
            ],
            'sender_organization' => [
                Rule::requiredIf($registering),
                Rule::prohibitedIf(! $registering),
                'nullable',
                'array',
            ],
            'sender_organization.mode' => [
                Rule::requiredIf($registering),
                Rule::in(['existing', 'new']),
            ],
            'sender_organization.id' => [
                Rule::requiredIf($registering && $senderMode === 'existing'),
                Rule::prohibitedIf($senderMode !== 'existing'),
                'integer',
                Rule::exists('sender_organizations', 'id')->where('is_active', true),
            ],
            'sender_organization.name' => [
                Rule::requiredIf($registering && $senderMode === 'new'),
                Rule::prohibitedIf($senderMode !== 'new'),
                'string',
                'max:200',
            ],
            'sender_organization.address' => [
                Rule::prohibitedIf($senderMode !== 'new'),
                'nullable',
                'string',
                'max:2000',
            ],
            'sender_organization.contact' => [
                Rule::prohibitedIf($senderMode !== 'new'),
                'nullable',
                'string',
                'max:150',
            ],
        ];
    }

    /**
     * @return array{
     *     outcome: SubmissionDecisionOutcome,
     *     note: string|null,
     *     agenda_number?: string,
     *     sender_organization?: array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}
     * }
     */
    public function decisionPayload(): array
    {
        $outcome = SubmissionDecisionOutcome::from((string) $this->validated('outcome'));
        $payload = [
            'outcome' => $outcome,
            'note' => $this->validated('note'),
        ];

        if ($outcome === SubmissionDecisionOutcome::Registered) {
            /** @var array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null} $sender */
            $sender = $this->validated('sender_organization');
            $payload['agenda_number'] = (string) $this->validated('agenda_number');
            $payload['sender_organization'] = $sender;
        }

        return $payload;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'agenda_number.regex' => 'Nomor agenda hanya boleh berisi huruf, angka, titik, garis miring, atau tanda hubung.',
        ];
    }
}
