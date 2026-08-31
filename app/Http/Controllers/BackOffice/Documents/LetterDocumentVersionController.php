<?php

namespace App\Http\Controllers\BackOffice\Documents;

use App\Actions\CreateLetterDocumentVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Documents\StoreDocumentVersionRequest;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class LetterDocumentVersionController extends Controller
{
    public function store(
        StoreDocumentVersionRequest $request,
        IncomingLetter $incomingLetter,
        CreateLetterDocumentVersion $createVersion,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $file = $request->file('document');
        abort_unless($file instanceof UploadedFile, 422);

        $createVersion->execute(
            actor: $actor,
            incomingLetter: $incomingLetter,
            file: $file,
            correctionReason: (string) $request->validated('correction_reason'),
        );

        return to_route('back-office.letters.documents.index', $incomingLetter)
            ->with('status', 'Versi dokumen resmi baru berhasil dibuat.');
    }
}
