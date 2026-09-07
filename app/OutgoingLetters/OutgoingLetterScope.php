<?php

namespace App\OutgoingLetters;

final readonly class OutgoingLetterScope
{
    /**
     * @param  list<int>  $positionIds
     * @param  list<int>  $executivePositionIds
     * @param  list<int>  $assistantPositionIds
     * @param  list<int>  $sectionHeadPositionIds
     * @param  list<int>  $generalAffairsOfficerPositionIds
     * @param  list<int>  $generalAffairsHeadPositionIds
     */
    public function __construct(
        public array $positionIds,
        public array $executivePositionIds,
        public array $assistantPositionIds,
        public array $sectionHeadPositionIds,
        public array $generalAffairsOfficerPositionIds,
        public array $generalAffairsHeadPositionIds,
    ) {}

    public function hasGlobalRegisterAccess(): bool
    {
        return $this->generalAffairsOfficerPositionIds !== []
            || $this->generalAffairsHeadPositionIds !== [];
    }
}
