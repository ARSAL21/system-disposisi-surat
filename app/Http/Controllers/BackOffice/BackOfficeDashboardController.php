<?php

namespace App\Http\Controllers\BackOffice;

use App\Actions\GetAdminDashboardData;
use App\Actions\GetBackOfficeDashboardData;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackOfficeDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetBackOfficeDashboardData $getIntakeDashboardData,
        GetAdminDashboardData $getAdminDashboardData
    ): Response {
        $user = $request->user();

        $isAdmin = $user instanceof User && (
            $user->hasRole(RoleName::SuperAdmin->value) ||
            $user->can(PermissionName::ViewUsers->value) ||
            $user->can(PermissionName::ViewAuthorization->value)
        );

        $adminDashboard = $isAdmin
            ? $getAdminDashboardData->execute($request)
            : null;

        $intakeDashboard = $user instanceof User && $user->can('viewAnyIntake', LetterSubmission::class)
            ? $getIntakeDashboardData->execute($request)
            : null;

        return Inertia::render('back-office/Dashboard', [
            'adminDashboard' => $adminDashboard,
            'intakeDashboard' => $intakeDashboard,
            'preview' => false,
        ]);
    }
}
