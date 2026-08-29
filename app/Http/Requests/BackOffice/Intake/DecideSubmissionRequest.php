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

    public function outcome(): SubmissionDecisionOutcome
    {
        return SubmissionDecisionOutcome::from((string) $this->validated('outcome'));
    }

    public function decisionNote(): ?string
    {
        $note = $this->validated('note');

        return is_string($note) ? $note : null;
    }

    /**
     * @return array{
     *     agenda_number: string,
     *     note: string|null,
     *     sender_organization: array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}
     * }
     */
    public function registrationPayload(): array
    {
        if ($this->outcome() !== SubmissionDecisionOutcome::Registered) {
            throw new \LogicException('Registration payload is only available for a REGISTERED decision.');
        }

        $sender = $this->validated('sender_organization');

        if (! is_array($sender)) {
            throw new \LogicException('Validated sender organization payload is missing.');
        }

        return [
            'agenda_number' => (string) $this->validated('agenda_number'),
            'note' => $this->decisionNote(),
            'sender_organization' => $this->normalizeSenderOrganization($sender),
        ];
    }

    /**
     * @param  array<mixed, mixed>  $sender
     * @return array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}
     */
    private function normalizeSenderOrganization(array $sender): array
    {
        $mode = $sender['mode'] ?? null;

        if ($mode === 'existing') {
            $id = $sender['id'] ?? null;

            if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
                throw new \LogicException('Validated sender organization ID is invalid.');
            }

            return ['mode' => 'existing', 'id' => (int) $id];
        }

        $name = $sender['name'] ?? null;
        $address = $sender['address'] ?? null;
        $contact = $sender['contact'] ?? null;

        if ($mode !== 'new'
            || ! is_string($name)
            || ! (is_string($address) || $address === null)
            || ! (is_string($contact) || $contact === null)) {
            throw new \LogicException('Validated new sender organization payload is invalid.');
        }

        return [
            'mode' => 'new',
            'name' => $name,
            'address' => $address,
            'contact' => $contact,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'agenda_number.regex' => 'Nomor agenda hanya boleh berisi huruf, angka, titik, garis miring, atau tanda hubung.',
        ];
    }
}
