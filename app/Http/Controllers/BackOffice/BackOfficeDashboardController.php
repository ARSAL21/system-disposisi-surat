<?php

namespace App\Http\Controllers\BackOffice;

use App\Actions\GetBackOfficeDashboardData;
use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackOfficeDashboardController extends Controller
{
    public function __invoke(Request $request, GetBackOfficeDashboardData $getDashboardData): Response
    {
        $user = $request->user();
        $intakeDashboard = $user instanceof User && $user->can('viewAnyIntake', LetterSubmission::class)
            ? $getDashboardData->execute($request)
            : null;

        return Inertia::render('back-office/Dashboard', [
            'intakeDashboard' => $intakeDashboard,
            'preview' => false,
        ]);
    }
}
