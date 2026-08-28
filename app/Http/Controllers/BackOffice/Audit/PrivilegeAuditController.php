<?php

namespace App\Http\Controllers\BackOffice\Audit;

use App\Actions\GetPrivilegeAuditWorkspace;
use App\Auditing\PrivilegeAuditCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Audit\ListPrivilegeAuditsRequest;
use Inertia\Inertia;
use Inertia\Response;

class PrivilegeAuditController extends Controller
{
    public function __invoke(
        ListPrivilegeAuditsRequest $request,
        GetPrivilegeAuditWorkspace $getPrivilegeAuditWorkspace,
    ): Response {
        $filters = $request->filters();
        $workspace = $getPrivilegeAuditWorkspace->execute($filters);

        return Inertia::render('back-office/privilege-audits/Index', [
            ...$workspace,
            'filters' => $filters,
            'filterOptions' => PrivilegeAuditCatalog::filterOptions(),
            'routes' => [
                'index' => route('back-office.privilege-audits.index'),
            ],
        ]);
    }
}
