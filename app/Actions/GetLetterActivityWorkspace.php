<?php

namespace App\Actions;

use App\Auditing\LetterActivityPresenter;
use App\Auditing\LetterActivityQuery;
use App\Auditing\LetterActivityTargetResolver;
use App\Enums\LetterActivityVisibility;
use App\Models\AuditLog;

class GetLetterActivityWorkspace
{
    public function __construct(
        private readonly LetterActivityQuery $query,
        private readonly LetterActivityTargetResolver $targetResolver,
        private readonly LetterActivityPresenter $presenter,
    ) {}

    /**
     * @param  array{action: string, source: string, actor: string, letter: string, date_from: string, date_to: string}  $filters
     * @return array{activities: array<string, mixed>, summary: array<string, int>, actors: list<array{value: string, label: string}>}
     */
    public function execute(
        array $filters,
        LetterActivityVisibility $visibility,
    ): array {
        $query = $this->query->build($filters);
        $summary = $this->query->summary($query);
        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $audits = $paginator->getCollection();
        $targets = $visibility === LetterActivityVisibility::Details
            ? $this->targetResolver->resolve($audits)
            : [];
        $activities = $audits->map(
            fn (AuditLog $audit): array => $this->presenter->present(
                $audit,
                $visibility,
                $targets[$audit->getKey()] ?? null,
            ),
        );

        return [
            'activities' => [
                'data' => $activities->values()->all(),
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
            'actors' => $visibility === LetterActivityVisibility::Details
                ? $this->query->actorOptions()
                : [],
        ];
    }
}
