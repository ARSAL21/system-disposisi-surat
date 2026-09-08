<?php

namespace App\Http\Controllers\BackOffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Reporting\ExportPeriodicReportsRequest;
use App\Models\User;
use App\Reporting\PeriodicReportCsvExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeriodicReportExportController extends Controller
{
    public function summary(
        ExportPeriodicReportsRequest $request,
        PeriodicReportCsvExporter $exporter,
    ): StreamedResponse {
        /** @var User $user */
        $user = $request->user();

        return $exporter->summary($user, $request->filters());
    }

    public function letters(
        ExportPeriodicReportsRequest $request,
        PeriodicReportCsvExporter $exporter,
    ): StreamedResponse {
        /** @var User $user */
        $user = $request->user();

        return $exporter->letters($user, $request->filters());
    }
}
