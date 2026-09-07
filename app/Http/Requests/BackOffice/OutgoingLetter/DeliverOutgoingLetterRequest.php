<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use App\Enums\OutgoingDeliveryMethod;
use App\Enums\SubmissionSource;
use App\Models\OutgoingLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class DeliverOutgoingLetterRequest extends FormRequest
{
    private ?bool $manualSource = null;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'delivery_method' => [Rule::requiredIf(fn (): bool => $this->isManual()), 'nullable', Rule::enum(OutgoingDeliveryMethod::class)],
            'recipient_name' => [Rule::requiredIf(fn (): bool => $this->isManual()), 'nullable', 'string', 'min:3', 'max:150'],
            'delivered_at' => [Rule::requiredIf(fn (): bool => $this->isManual()), 'nullable', 'date', 'before_or_equal:now'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['recipient_name', 'tracking_number', 'delivery_note'] as $field) {
            $value = $this->input($field);
            $this->merge([$field => is_string($value) && trim($value) !== '' ? trim($value) : null]);
        }
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isManual() && $this->input('delivery_method') === OutgoingDeliveryMethod::Portal->value) {
                    $validator->errors()->add('delivery_method', 'Surat manual tidak dapat menggunakan metode portal.');
                }
            },
        ];
    }

    private function isManual(): bool
    {
        if ($this->manualSource !== null) {
            return $this->manualSource;
        }

        $letter = $this->route('outgoingLetter');
        if (! $letter instanceof OutgoingLetter) {
            return $this->manualSource = false;
        }

        return $this->manualSource = $letter->incomingLetter?->submission()->toBase()->value('source')
            === SubmissionSource::Manual->value;
    }
}
