<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\UploadStandaloneOutgoingManualSignatureScan;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\UploadStandaloneManualSignatureScanRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreStandaloneOutgoingManualSignatureScanController extends Controller
{
    public function __invoke(UploadStandaloneManualSignatureScanRequest $request, OutgoingLetter $outgoingLetter, UploadStandaloneOutgoingManualSignatureScan $action): RedirectResponse
    {
        Gate::authorize('uploadStandaloneManualScan', $outgoingLetter);
        $user = $request->user();
        $file = $request->file('manual_scan');
        abort_unless($user instanceof User && $file instanceof UploadedFile, 422);
        $action->execute($user, $outgoingLetter, $file);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Scan tanda tangan diunggah dan menunggu pemeriksaan Kabag unit.']);

        return back();
    }
}
