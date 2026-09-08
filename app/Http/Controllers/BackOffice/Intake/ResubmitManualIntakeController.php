<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\ResubmitManualSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\SaveManualIntakeRequest;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResubmitManualIntakeController extends Controller
{
    public function __invoke(
        SaveManualIntakeRequest $request,
        LetterSubmission $submission,
        ResubmitManualSubmission $resubmitManualSubmission,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $submission = $resubmitManualSubmission->execute(
            $actor,
            $submission,
            $request->manualIntakeData(),
            $request->document(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Perbaikan surat manual berhasil diajukan kembali kepada Kepala Bagian Umum.'),
        ]);

        return to_route('back-office.intake.submissions.show', $submission);
    }
}
