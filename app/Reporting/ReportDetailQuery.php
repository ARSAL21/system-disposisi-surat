<?php

namespace App\Reporting;

use App\Models\IncomingLetter;
use App\Models\User;

final class ReportDetailQuery
{
    public function __construct(
        private readonly ReportLetterQuery $letterQuery,
        private readonly ReportScopeResolver $scopeResolver,
        private readonly ReportBranchVisibility $branchVisibility,
    ) {}

    public function find(User $user, IncomingLetter $incomingLetter): IncomingLetter
    {
        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            abort(404);
        }

        return $this->letterQuery->detailScope($user)
            ->whereKey($incomingLetter->getKey())
            ->select('incoming_letters.*')
            ->selectSub($this->letterQuery->terminalCompletionSubquery(), 'report_completed_at')
            ->with([
                'submission:id,source',
                'senderOrganization:id,name',
                'currentRoute.recipientPosition.organizationalUnit:id,name',
                'currentRoute.recipientPosition.positionLevel:id,code',
                'currentRoute.recipientPosition.activeAssignment.user:id,name',
                'currentRoute.routedBy:id,name',
                'currentRoute.routedByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.instructionLabels:id,code,name,sort_order',
                'currentRoute.disposition.createdBy:id,name',
                'currentRoute.disposition.createdByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients' => fn ($recipient) => $this
                    ->branchVisibility->firstRecipients($recipient, $scope, false)
                    ->orderBy('received_at')
                    ->orderBy('id'),
                'currentRoute.disposition.recipients.recipientPosition.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.recipientPosition.positionLevel:id,code',
                'currentRoute.disposition.recipients.recipientPosition.activeAssignment.user:id,name',
                'currentRoute.disposition.recipients.childDispositions.instructionLabels:id,code,name,sort_order',
                'currentRoute.disposition.recipients.childDispositions.createdBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.createdByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients' => fn ($recipient) => $this
                    ->branchVisibility->intermediateRecipients($recipient, $scope, false)
                    ->orderBy('received_at')
                    ->orderBy('id'),
                'currentRoute.disposition.recipients.childDispositions.recipients.recipientPosition.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
                'currentRoute.disposition.recipients.childDispositions.recipients.recipientPosition.activeAssignment.user:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.completedBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.completedByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.followUps',
                'currentRoute.disposition.recipients.childDispositions.recipients.followUps.createdBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.followUps.createdByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.instructionLabels:id,code,name,sort_order',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.createdBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.createdByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients' => fn ($recipient) => $this
                    ->branchVisibility->terminalRecipients($recipient, $scope, false)
                    ->orderBy('received_at')
                    ->orderBy('id'),
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.recipientPosition.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.recipientPosition.activeAssignment.user:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.completedBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.completedByPositionAssignment.position.organizationalUnit:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.followUps',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.followUps.createdBy:id,name',
                'currentRoute.disposition.recipients.childDispositions.recipients.childDispositions.recipients.followUps.createdByPositionAssignment.position.organizationalUnit:id,name',
            ])
            ->firstOrFail();
    }
}
