<?php

namespace App\Http\Requests\BackOffice\Reporting;

use App\Enums\PermissionName;
use App\Models\IncomingLetter;
use Illuminate\Support\Facades\Gate;

class ExportPeriodicReportsRequest extends ListPeriodicReportsRequest
{
    public function authorize(): bool
    {
        parent::authorize();

        Gate::authorize('exportReports', IncomingLetter::class);

        return $this->user()?->can(PermissionName::ExportReports->value) === true;
    }
}
