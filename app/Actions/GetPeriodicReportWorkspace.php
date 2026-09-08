<?php

namespace App\Actions;

use App\Models\IncomingLetter;
use App\Models\User;
use App\Reporting\PeriodicReportPresenter;
use App\Reporting\ReportLetterQuery;
use App\Reporting\ReportMetricsQuery;
use App\Reporting\ReportOrganizationGraphQuery;
use App\Reporting\ReportScopeResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use LogicException;

final class GetPeriodicReportWorkspace
{
    public function __construct(
        private readonly ReportLetterQuery $letterQuery,
        private readonly ReportMetricsQuery $metricsQuery,
        private readonly ReportOrganizationGraphQuery $graphQuery,
        private readonly ReportScopeResolver $scopeResolver,
        private readonly PeriodicReportPresenter $presenter,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $user, array $filters): array
    {
        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            throw new LogicException('Authorized report requests require a valid Position scope.');
        }

        $paginator = $this->letterQuery->list($user, $filters)
            ->paginate(20)
            ->withQueryString();

        return [
            'summary' => $this->metricsQuery->summary($user, $filters),
            'sourceBreakdown' => $this->metricsQuery->sourceBreakdown($user, $filters),
            'intakeFunnel' => $this->metricsQuery->intakeFunnel($user, $filters),
            'senderBreakdown' => $this->metricsQuery->senderBreakdown($user, $filters),
            'trend' => $this->metricsQuery->trend($user, $filters),
            'letters' => [
                'data' => $this->presenter->letters($paginator->getCollection(), $user, $filters),
                'pagination' => $this->pagination($paginator),
            ],
            'organizationGraph' => $this->graphQuery->build($user, $filters),
            'filters' => $filters,
            'scope' => [
                'mode' => $scope->mode,
                'label' => $scope->label,
                'description' => $scope->description,
            ],
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, IncomingLetter>  $paginator
     * @return array{current_page: int, last_page: int, from: int, to: int, total: int, previous_url: string|null, next_url: string|null}
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
            'total' => $paginator->total(),
            'previous_url' => $paginator->previousPageUrl(),
            'next_url' => $paginator->nextPageUrl(),
        ];
    }
}
