<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use App\Enums\OutgoingDeliveryMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\SubmissionSource;
use App\Models\OutgoingLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class DeliverOutgoingLetterRequest extends FormRequest
{
    private ?bool $manualSource = null;

    private ?bool $standalone = null;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'delivery_method' => [Rule::requiredIf(fn (): bool => $this->isManual()), 'nullable', Rule::enum(OutgoingDeliveryMethod::class)],
            'recipient_name' => [Rule::requiredIf(fn (): bool => $this->requiresPhysicalEvidence()), 'nullable', 'string', 'min:3', 'max:150'],
            'delivered_at' => [Rule::requiredIf(fn (): bool => $this->requiresPhysicalEvidence()), 'nullable', 'date', 'before_or_equal:now'],
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
                $method = $this->input('delivery_method');
                if ($this->isStandalone()) {
                    if ($method === OutgoingDeliveryMethod::Portal->value) {
                        $validator->errors()->add('delivery_method', 'Surat keluar mandiri tidak dapat menggunakan metode portal.');
                    }
                    if ($method === OutgoingDeliveryMethod::Email->value && ! $this->standaloneRecipientEmail()) {
                        $validator->errors()->add('delivery_method', 'Alamat email penerima belum tersedia pada konsep surat.');
                    }
                } elseif ($this->isManual() && in_array($method, [OutgoingDeliveryMethod::Portal->value, OutgoingDeliveryMethod::Email->value], true)) {
                    $validator->errors()->add('delivery_method', 'Surat manual tidak dapat menggunakan metode portal atau email.');
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

        if ($letter->origin === OutgoingLetterOrigin::Standalone) {
            return $this->manualSource = true;
        }

        return $this->manualSource = $letter->incomingLetter?->submission()->toBase()->value('source')
            === SubmissionSource::Manual->value;
    }

    private function isStandalone(): bool
    {
        if ($this->standalone !== null) {
            return $this->standalone;
        }

        return $this->standalone = $this->route('outgoingLetter') instanceof OutgoingLetter
            && $this->route('outgoingLetter')->origin === OutgoingLetterOrigin::Standalone;
    }

    private function requiresPhysicalEvidence(): bool
    {
        return $this->isManual() && ! ($this->isStandalone() && $this->input('delivery_method') === OutgoingDeliveryMethod::Email->value);
    }

    private function standaloneRecipientEmail(): bool
    {
        $letter = $this->route('outgoingLetter');
        if (! $letter instanceof OutgoingLetter) {
            return false;
        }

        return is_string($letter->standaloneDraft?->recipient_email) && $letter->standaloneDraft->recipient_email !== '';
    }
}
