<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Http\Controllers\Controller;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use App\Models\SubmissionDecision;
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ManualIntakeController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('createManual', LetterSubmission::class);

        return Inertia::render('back-office/intake/manual/Create', [
            'mode' => 'create',
            'routes' => $this->routes(route('back-office.intake.manual.store')),
        ]);
    }

    public function edit(LetterSubmission $submission): Response
    {
        Gate::authorize('viewManualRevision', $submission);

        $submission->load(['document', 'latestReview', 'latestDecision']);
        $timezone = (string) config('letter-activity.timezone', 'Asia/Makassar');
        $document = $submission->document;
        $review = $submission->latestReview;
        $decision = $submission->latestDecision;

        return Inertia::render('back-office/intake/manual/Create', [
            'mode' => 'revision',
            'initial' => [
                'sender_organization_name' => $submission->sender_organization_name,
                'contact_name' => $submission->contact_name,
                'contact_email' => $submission->contact_email ?? '',
                'contact_phone' => $submission->contact_phone ?? '',
                'received_at' => $submission->received_at?->setTimezone($timezone)->format('Y-m-d\TH:i') ?? '',
                'external_letter_number' => $submission->external_letter_number ?? '',
                'external_letter_date' => $submission->external_letter_date?->toDateString() ?? '',
                'subject' => $submission->subject,
                'summary' => $submission->summary ?? '',
                'checklist' => SubmissionScreeningChecklist::present(
                    $review instanceof SubmissionReview ? $review->checklist : null,
                ),
                'screening_note' => $review instanceof SubmissionReview ? ($review->note ?? '') : '',
                'existing_document' => $document instanceof SubmissionDocument
                    ? [
                        'original_filename' => $document->original_filename,
                        'size_bytes' => $document->size_bytes,
                    ]
                    : null,
                'return_note' => $decision instanceof SubmissionDecision ? $decision->note : null,
            ],
            'routes' => $this->routes(route('back-office.intake.manual.resubmit', $submission)),
        ]);
    }

    /** @return array{store: string, intake_index: string, incoming_register: string} */
    private function routes(string $store): array
    {
        return [
            'store' => $store,
            'intake_index' => route('back-office.intake.submissions.index'),
            'incoming_register' => route('back-office.incoming-letters.index'),
        ];
    }
}
