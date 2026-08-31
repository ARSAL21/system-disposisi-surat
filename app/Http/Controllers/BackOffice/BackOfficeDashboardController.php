<?php

namespace App\Http\Controllers\BackOffice;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackOfficeDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $intakeDashboard = null;

        if ($user instanceof User && $user->can('viewAnyIntake', LetterSubmission::class)) {
            $metrics = [
                'submitted_count' => LetterSubmission::query()
                    ->where('status', SubmissionStatus::Submitted)
                    ->count(),
                'internal_revision_count' => LetterSubmission::query()
                    ->where('status', SubmissionStatus::InternalRevisionRequired)
                    ->count(),
                'ready_for_approval_count' => LetterSubmission::query()
                    ->where('status', SubmissionStatus::ReadyForApproval)
                    ->count(),
                'registered_count' => LetterSubmission::query()
                    ->where('status', SubmissionStatus::Registered)
                    ->count(),
            ];

            $submissions = LetterSubmission::query()
                ->whereIn('status', [
                    SubmissionStatus::Submitted,
                    SubmissionStatus::InternalRevisionRequired,
                ])
                ->with([
                    'document',
                    'latestDecision',
                ])
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $recentSubmissions = $submissions->map(function (LetterSubmission $submission): array {
                $hasDocument = $submission->relationLoaded('document') && $submission->document !== null;
                $internalRevisionNote = $submission->status === SubmissionStatus::InternalRevisionRequired
                    ? $submission->latestDecision?->note
                    : null;

                return [
                    'public_id' => $submission->public_id,
                    'source' => $submission->source->value,
                    'status' => $submission->status->value,
                    'sender_organization_name' => $submission->sender_organization_name,
                    'contact_name' => $submission->contact_name,
                    'contact_email' => $submission->contact_email,
                    'contact_phone' => $submission->contact_phone,
                    'external_letter_number' => $submission->external_letter_number,
                    'external_letter_date' => $submission->external_letter_date?->format('Y-m-d'),
                    'subject' => $submission->subject,
                    'summary' => $submission->summary,
                    'submitted_at' => $submission->submitted_at?->toISOString() ?? now()->toISOString(),
                    'document' => $hasDocument ? [
                        'original_filename' => $submission->document->original_filename,
                        'mime_type' => $submission->document->mime_type,
                        'size_bytes' => (int) $submission->document->size_bytes,
                    ] : null,
                    'internal_revision_note' => $internalRevisionNote,
                    'links' => [
                        'show' => route('back-office.intake.submissions.show', $submission),
                        'document_preview' => $hasDocument
                            ? route('back-office.intake.submissions.document.show', $submission)
                            : null,
                        'document_download' => $hasDocument
                            ? route('back-office.intake.submissions.document.download', $submission)
                            : null,
                    ],
                ];
            })->all();

            $intakeDashboard = [
                'metrics' => $metrics,
                'recent_submissions' => $recentSubmissions,
            ];
        }

        return Inertia::render('back-office/Dashboard', [
            'intakeDashboard' => $intakeDashboard,
            'preview' => false,
        ]);
    }
}
