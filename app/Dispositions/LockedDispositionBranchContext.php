<?php

namespace App\Dispositions;

use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class LockedDispositionBranchContext
{
    /**
     * @param  Collection<int, DispositionRecipient>  $terminalBranches
     */
    public function __construct(
        public User $actor,
        public IncomingLetter $letter,
        public Collection $terminalBranches,
        public DispositionRecipient $branch,
        public PositionAssignment $actorPositionAssignment,
    ) {}
}
