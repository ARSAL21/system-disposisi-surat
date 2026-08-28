<?php

namespace App\Actions;

use App\Auditing\PrivilegeAuditPresenter;
use App\Auditing\PrivilegeAuditQuery;
use App\Auditing\PrivilegeAuditTargetResolver;
use App\Models\AuditLog;

class GetPrivilegeAuditWorkspace
{
    public function __construct(
        private readonly PrivilegeAuditQuery $privilegeAuditQuery,
        private readonly PrivilegeAuditTargetResolver $targetResolver,
        private readonly PrivilegeAuditPresenter $presenter,
    ) {}

    /**
     * @param  array{action: string, source: string, actor: string, target_type: string, target: string, date_from: string, date_to: string}  $filters
     * @return array{audits: array<string, mixed>, summary: array{total: int, web: int, console: int}}
     */
    public function execute(array $filters): array
    {
        $query = $this->privilegeAuditQuery->build($filters);
        $summary = $this->privilegeAuditQuery->summary($query);
        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
        $audits = $paginator->getCollection();
        $targets = $this->targetResolver->resolve($audits);
        $presentedAudits = $audits->map(
            fn (AuditLog $audit): array => $this->presenter->present(
                $audit,
                $targets[$audit->subject_type][$audit->subject_id] ?? null,
            ),
        );

        return [
            'audits' => [
                'data' => $presentedAudits->values()->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ],
            'summary' => $summary,
        ];
    }
}
