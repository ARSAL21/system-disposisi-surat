<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\UploadSignedOutgoingLetterDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\UploadSignedOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreSignedOutgoingLetterDocumentController extends Controller
{
    public function __invoke(
        UploadSignedOutgoingLetterRequest $request,
        OutgoingLetter $outgoingLetter,
        UploadSignedOutgoingLetterDocument $action,
    ): RedirectResponse {
        Gate::authorize('uploadSignedDocument', $outgoingLetter);
        $user = $request->user();
        $file = $request->file('signed_document');
        abort_unless($user instanceof User && $file instanceof UploadedFile, 422);
        $action->execute($user, $outgoingLetter, $file, $request->string('upload_note')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versi PDF final berhasil diunggah.']);

        return back();
    }
}
