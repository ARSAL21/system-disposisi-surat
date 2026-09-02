<?php

namespace App\Reporting;

final readonly class ReportScope
{
    /**
     * @param  list<int>  $executivePositionIds
     * @param  list<int>  $assistantPositionIds
     * @param  list<int>  $sectionHeadPositionIds
     */
    public function __construct(
        public string $mode,
        public string $label,
        public string $description,
        public bool $hasGlobalAggregates,
        public bool $hasGlobalDetails,
        public array $executivePositionIds,
        public array $assistantPositionIds,
        public array $sectionHeadPositionIds,
    ) {}
}
