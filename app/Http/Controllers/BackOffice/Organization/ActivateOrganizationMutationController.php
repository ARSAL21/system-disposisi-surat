<?php

namespace App\Http\Controllers\BackOffice\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ActivateOrganizationMutationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'destination' => ['sometimes', Rule::in(['structure', 'assignments'])],
        ]);
        $destination = $validated['destination'] ?? null;

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mode perubahan administratif aktif selama 15 menit.',
        ]);

        if ($destination !== null) {
            return to_route("back-office.organization.{$destination}.index");
        }

        return redirect()->back();
    }
}
