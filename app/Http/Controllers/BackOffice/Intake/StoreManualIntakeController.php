<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\CreateManualSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\SaveManualIntakeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StoreManualIntakeController extends Controller
{
    public function __invoke(
        SaveManualIntakeRequest $request,
        CreateManualSubmission $createManualSubmission,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $document = $request->document();

        if ($document === null) {
            throw ValidationException::withMessages([
                'document' => 'Hasil scan PDF wajib diunggah.',
            ]);
        }

        $submission = $createManualSubmission->execute(
            $actor,
            $request->manualIntakeData(),
            $document,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Surat fisik berhasil dicatat dan diajukan kepada Kepala Bagian Umum.'),
        ]);

        return to_route('back-office.intake.submissions.show', $submission);
    }
}
