<?php

namespace App\Http\Requests\BackOffice\Intake;

use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class SaveManualIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        if ($submission instanceof LetterSubmission) {
            Gate::authorize('resubmitManual', $submission);
        } else {
            Gate::authorize('createManual', LetterSubmission::class);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'sender_organization_name',
            'contact_name',
            'contact_email',
            'contact_phone',
            'received_at',
            'external_letter_number',
            'external_letter_date',
            'subject',
            'summary',
            'screening_note',
        ];
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);
            $normalized[$field] = is_string($value) && trim($value) !== ''
                ? trim($value)
                : null;
        }

        $this->merge($normalized);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $creating = ! ($this->route('submission') instanceof LetterSubmission);

        return [
            'sender_organization_name' => ['required', 'string', 'max:200'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'received_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'external_letter_number' => ['nullable', 'string', 'max:100'],
            'external_letter_date' => ['nullable', 'date_format:Y-m-d'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'document' => [
                $creating ? 'required' : 'nullable',
                File::types(['pdf'])->max('20mb'),
                'extensions:pdf',
            ],
            'checklist' => ['required', 'array', 'size:'.count(SubmissionScreeningChecklist::keys())],
            'checklist.*.id' => ['required', 'string', 'distinct'],
            'checklist.*.checked' => ['required', 'boolean'],
            'screening_note' => ['nullable', 'string', 'min:10', 'max:2000'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateChecklist($validator);
            $this->validateOfficeDates($validator);
        }];
    }

    /**
     * @return array{
     *     sender_organization_name: string,
     *     contact_name: string,
     *     contact_email: string|null,
     *     contact_phone: string|null,
     *     received_at: CarbonImmutable,
     *     external_letter_number: string|null,
     *     external_letter_date: CarbonImmutable|null,
     *     subject: string,
     *     summary: string|null,
     *     checklist: list<array{id: string, checked: bool}>,
     *     screening_note: string|null
     * }
     */
    public function manualIntakeData(): array
    {
        $data = $this->validated();
        /** @var list<array{id: string, checked: bool}> $checklist */
        $checklist = $data['checklist'];
        $timezone = (string) config('letter-activity.timezone', 'Asia/Makassar');
        $externalLetterDate = $this->nullableString($data['external_letter_date'] ?? null);

        return [
            'sender_organization_name' => (string) $data['sender_organization_name'],
            'contact_name' => (string) $data['contact_name'],
            'contact_email' => $this->nullableString($data['contact_email'] ?? null),
            'contact_phone' => $this->nullableString($data['contact_phone'] ?? null),
            'received_at' => CarbonImmutable::createFromFormat('!Y-m-d\TH:i', (string) $data['received_at'], $timezone)->utc(),
            'external_letter_number' => $this->nullableString($data['external_letter_number'] ?? null),
            'external_letter_date' => $externalLetterDate !== null
                ? CarbonImmutable::createFromFormat('!Y-m-d', $externalLetterDate, $timezone)
                : null,
            'subject' => (string) $data['subject'],
            'summary' => $this->nullableString($data['summary'] ?? null),
            'checklist' => $checklist,
            'screening_note' => $this->nullableString($data['screening_note'] ?? null),
        ];
    }

    public function document(): ?UploadedFile
    {
        $document = $this->file('document');

        return $document instanceof UploadedFile ? $document : null;
    }

    private function validateChecklist(Validator $validator): void
    {
        if ($validator->errors()->has('checklist')) {
            return;
        }

        /** @var list<array{id: string, checked: bool}> $items */
        $items = $this->input('checklist', []);
        $submittedKeys = array_column($items, 'id');
        $expectedKeys = SubmissionScreeningChecklist::keys();
        sort($submittedKeys);
        sort($expectedKeys);

        if ($submittedKeys !== $expectedKeys
            || ! SubmissionScreeningChecklist::isComplete(SubmissionScreeningChecklist::normalize($items))) {
            $validator->errors()->add(
                'checklist',
                'Seluruh checklist pemeriksaan resmi wajib dikonfirmasi.',
            );
        }
    }

    private function validateOfficeDates(Validator $validator): void
    {
        if ($validator->errors()->has('received_at')) {
            return;
        }

        $timezone = (string) config('letter-activity.timezone', 'Asia/Makassar');
        $receivedAt = CarbonImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            (string) $this->input('received_at'),
            $timezone,
        );

        if ($receivedAt->isAfter(CarbonImmutable::now($timezone)->addMinute())) {
            $validator->errors()->add('received_at', 'Waktu penerimaan tidak boleh berada di masa depan.');
        }

        $externalDate = $this->input('external_letter_date');
        if (is_string($externalDate) && $externalDate > $receivedAt->toDateString()) {
            $validator->errors()->add(
                'external_letter_date',
                'Tanggal surat tidak boleh melewati tanggal penerimaan.',
            );
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'Nomor kontak hanya boleh berisi angka dan tanda telepon umum.',
            'document.extensions' => 'Nama berkas harus menggunakan ekstensi .pdf.',
        ];
    }
}
