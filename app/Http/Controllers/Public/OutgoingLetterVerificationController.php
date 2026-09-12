<?php

namespace App\Http\Controllers\Public;

use App\Enums\OutgoingLetterElectronicApprovalMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterElectronicApproval;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OutgoingLetterVerificationController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1, 404);

        $approval = OutgoingLetterElectronicApproval::query()
            ->where('verification_token_hash', hash('sha256', $token))
            ->where('method', OutgoingLetterElectronicApprovalMethod::Qr->value)
            ->whereHas('outgoingLetter', fn ($letter) => $letter->where('origin', OutgoingLetterOrigin::Standalone->value))
            ->with([
                'outgoingLetter.standaloneDraft.organizationalUnit:id,name',
                'approvedByPositionAssignment.position:id,name',
                'finalDocumentVersion:id,sha256',
            ])
            ->firstOrFail();
        $letter = $approval->outgoingLetter;
        $replacement = OutgoingLetter::query()
            ->where('corrects_outgoing_letter_id', $letter->getKey())
            ->where('origin', OutgoingLetterOrigin::Standalone->value)
            ->where('status', OutgoingLetterStatus::Delivered->value)
            ->orderByDesc('id')
            ->first(['outgoing_number', 'letter_date']);

        return Inertia::render('public/outgoing-letter-verification/Show', [
            'verification' => [
                'status' => 'VALID',
                'outgoing_number' => $letter->outgoing_number,
                'letter_date' => $letter->letter_date?->toDateString(),
                'originating_unit_name' => $letter->standaloneDraft?->organizationalUnit?->name,
                'approved_position' => $approval->approvedByPositionAssignment->position->name,
                'approved_at' => $approval->approved_at->toISOString(),
                'sha256_fingerprint' => $approval->finalDocumentVersion?->sha256,
                'replacement' => $replacement === null ? null : [
                    'outgoing_number' => $replacement->outgoing_number,
                    'letter_date' => $replacement->letter_date?->toDateString(),
                ],
            ],
        ]);
    }
}
