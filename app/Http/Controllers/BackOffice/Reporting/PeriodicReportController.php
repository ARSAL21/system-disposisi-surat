<?php

namespace App\Http\Controllers\BackOffice\Reporting;

use App\Actions\GetPeriodicReportWorkspace;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Reporting\ListPeriodicReportsRequest;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Reporting\PeriodicReportPresenter;
use App\Reporting\ReportDetailQuery;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PeriodicReportController extends Controller
{
    public function index(
        ListPeriodicReportsRequest $request,
        GetPeriodicReportWorkspace $workspace,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $props = $workspace->handle($user, $request->filters());

        return Inertia::render('back-office/reports/Index', [
            ...$props,
            'routes' => $this->routes(),
            'canExport' => $user->can(PermissionName::ExportReports->value),
            'preview' => false,
        ]);
    }

    public function show(
        ListPeriodicReportsRequest $request,
        IncomingLetter $incomingLetter,
        GetPeriodicReportWorkspace $workspace,
        ReportDetailQuery $detailQuery,
        PeriodicReportPresenter $presenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        Gate::authorize('viewReport', $incomingLetter);
        $letter = $detailQuery->find($user, $incomingLetter);
        $props = $workspace->handle($user, $request->filters());

        return Inertia::render('back-office/reports/Show', [
            ...$props,
            'report' => $presenter->detail($letter),
            'routes' => $this->routes(),
            'canExport' => $user->can(PermissionName::ExportReports->value),
            'preview' => false,
        ]);
    }

    /** @return array{index: string, export_summary: string, export_letters: string} */
    private function routes(): array
    {
        return [
            'index' => route('back-office.reports.index'),
            'export_summary' => route('back-office.reports.exports.summary'),
            'export_letters' => route('back-office.reports.exports.letters'),
        ];
    }
}
