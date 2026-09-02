<?php

namespace App\Reporting;

use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\LetterRoute;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class ReportOrganizationGraphQuery
{
    public function __construct(
        private readonly ReportLetterQuery $letterQuery,
        private readonly ReportScopeResolver $scopeResolver,
        private readonly ReportBranchVisibility $branchVisibility,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return array{generated_at: string, executives: list<array<string, mixed>>}
     */
    public function build(User $user, array $filters): array
    {
        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            return ['generated_at' => now()->toISOString(), 'executives' => []];
        }

        $letterIds = $this->letterQuery->aggregate($user, $filters)
            ->select('incoming_letters.id');
        $routes = LetterRoute::query()
            ->whereIn('incoming_letter_id', $letterIds)
            ->when(! $scope->hasGlobalAggregates, fn (Builder $query): Builder => $query
                ->whereHas('disposition.recipients', fn ($recipient) => $this
                    ->branchVisibility->firstRecipients($recipient, $scope, true)))
            ->with([
                'recipientPosition.organizationalUnit:id,name',
                'recipientPosition.activeAssignment.user:id,name',
                'disposition.recipients' => fn ($recipient) => $this
                    ->branchVisibility->firstRecipients($recipient, $scope, true),
                'disposition.recipients.recipientPosition.organizationalUnit:id,name',
                'disposition.recipients.recipientPosition.activeAssignment.user:id,name',
                'disposition.recipients.childDispositions.recipients' => fn ($recipient) => $this
                    ->branchVisibility->terminalRecipients($recipient, $scope, true),
                'disposition.recipients.childDispositions.recipients.recipientPosition.organizationalUnit:id,name',
                'disposition.recipients.childDispositions.recipients.recipientPosition.activeAssignment.user:id,name',
                'disposition.recipients.childDispositions.recipients.followUps:id,disposition_recipient_id,created_at',
            ])
            ->orderBy('id')
            ->get();
        $executives = [];

        foreach ($routes as $route) {
            $executiveKey = (string) $route->recipientPosition->code;
            $executive = $executives[$executiveKey] ?? $this->newNode(
                'aggregate-executive-'.$route->recipientPosition->code,
                $route->recipientPosition,
            );
            $this->touch($executive, $route->routed_at);

            if (! $route->disposition instanceof Disposition) {
                $this->addStatus($executive, DispositionRecipientStatus::Pending->value, $route->routed_at);
                $executives[$executiveKey] = $executive;

                continue;
            }

            $this->touch($executive, $route->disposition->created_at);

            foreach ($route->disposition->recipients as $assistantRecipient) {
                $assistantKey = (string) $assistantRecipient->recipientPosition->code;
                $assistant = $executive['children'][$assistantKey] ?? $this->newNode(
                    'aggregate-'.$route->recipientPosition->code.'-'.$assistantRecipient->recipientPosition->code,
                    $assistantRecipient->recipientPosition,
                );
                $this->touch($assistant, $assistantRecipient->received_at);

                if ($assistantRecipient->childDispositions->count() > 1) {
                    throw DispositionStateConflict::inconsistentGraph();
                }

                $childDisposition = $assistantRecipient->childDispositions->first();
                if (! $childDisposition instanceof Disposition || $childDisposition->recipients->isEmpty()) {
                    $this->addStatus(
                        $assistant,
                        DispositionRecipientStatus::Pending->value,
                        $assistantRecipient->received_at,
                    );
                    $this->addStatus(
                        $executive,
                        DispositionRecipientStatus::Pending->value,
                        $assistantRecipient->received_at,
                    );
                    $executive['children'][$assistantKey] = $assistant;

                    continue;
                }

                $this->touch($assistant, $childDisposition->created_at);

                foreach ($childDisposition->recipients as $terminalRecipient) {
                    $sectionKey = (string) $terminalRecipient->recipientPosition->code;
                    $section = $assistant['children'][$sectionKey] ?? $this->newNode(
                        'aggregate-'.$route->recipientPosition->code.'-'.$assistantRecipient->recipientPosition->code.'-'.$terminalRecipient->recipientPosition->code,
                        $terminalRecipient->recipientPosition,
                    );
                    $activityAt = $this->lastActivityAt($terminalRecipient);
                    $this->addStatus($section, $terminalRecipient->status->value, $activityAt);
                    $this->addStatus($assistant, $terminalRecipient->status->value, $activityAt);
                    $this->addStatus($executive, $terminalRecipient->status->value, $activityAt);
                    $assistant['children'][$sectionKey] = $section;
                }

                $executive['children'][$assistantKey] = $assistant;
            }

            $executives[$executiveKey] = $executive;
        }

        $finishedExecutives = [];

        foreach ($executives as $executive) {
            $finishedExecutives[] = $this->finishNode($executive);
        }

        return [
            'generated_at' => now()->toISOString(),
            'executives' => $finishedExecutives,
        ];
    }

    /** @return array<string, mixed> */
    private function newNode(string $reference, Position $position): array
    {
        return [
            'reference' => $reference,
            'recipient_position' => $this->position($position),
            'counts' => ['pending' => 0, 'in_progress' => 0, 'completed' => 0],
            'last_activity_at' => null,
            'oldest_attention_at' => null,
            'children' => [],
        ];
    }

    /** @param array<string, mixed> $node */
    private function addStatus(array &$node, string $status, ?CarbonInterface $activityAt): void
    {
        $key = match ($status) {
            DispositionRecipientStatus::Completed->value => 'completed',
            DispositionRecipientStatus::InProgress->value => 'in_progress',
            default => 'pending',
        };
        $node['counts'][$key]++;
        $this->touch($node, $activityAt);

        if ($status !== DispositionRecipientStatus::Completed->value
            && $activityAt instanceof CarbonInterface
            && $activityAt->lessThanOrEqualTo(now()->subHours(48))) {
            $current = $node['oldest_attention_at'];
            if (! $current instanceof CarbonInterface || $activityAt->lessThan($current)) {
                $node['oldest_attention_at'] = $activityAt;
            }
        }
    }

    /** @param array<string, mixed> $node */
    private function touch(array &$node, ?CarbonInterface $activityAt): void
    {
        if (! $activityAt instanceof CarbonInterface) {
            return;
        }

        $current = $node['last_activity_at'];
        if (! $current instanceof CarbonInterface || $activityAt->greaterThan($current)) {
            $node['last_activity_at'] = $activityAt;
        }
    }

    private function lastActivityAt(DispositionRecipient $recipient): ?CarbonInterface
    {
        $timestamps = collect([
            $recipient->received_at,
            $recipient->started_at,
            $recipient->completed_at,
            $recipient->followUps->max('created_at'),
        ])->filter(fn (mixed $timestamp): bool => $timestamp instanceof CarbonInterface);

        return $timestamps->sortByDesc(fn (CarbonInterface $timestamp): int => $timestamp->getTimestamp())->first();
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function finishNode(array $node): array
    {
        $children = array_values(array_map(
            fn (array $child): array => $this->finishNode($child),
            $node['children'],
        ));
        $counts = $node['counts'];
        $total = $counts['pending'] + $counts['in_progress'] + $counts['completed'];
        $attentionAt = $node['oldest_attention_at'];
        $idleHours = $attentionAt instanceof CarbonInterface
            ? (int) floor($attentionAt->diffInSeconds(now()) / 3600)
            : null;

        return [
            'reference' => $node['reference'],
            'recipient_position' => $node['recipient_position'],
            'progress' => [
                'total' => $total,
                'pending' => $counts['pending'],
                'in_progress' => $counts['in_progress'],
                'completed' => $counts['completed'],
                'percent_complete' => $total === 0
                    ? 0
                    : (int) floor(($counts['completed'] / $total) * 100),
            ],
            'last_activity_at' => $node['last_activity_at'] instanceof CarbonInterface
                ? $node['last_activity_at']->toISOString()
                : null,
            'attention' => [
                'needs_attention' => $idleHours !== null,
                'idle_hours' => $idleHours,
                'reason' => $idleHours === null
                    ? null
                    : "Terdapat cabang tanpa aktivitas selama {$idleHours} jam.",
            ],
            'children' => $children,
        ];
    }

    /** @return array{code: string, name: string, unit_name: string|null, official_name: string|null} */
    private function position(Position $position): array
    {
        $assignment = $position->activeAssignment;

        return [
            'code' => $position->code,
            'name' => $position->name,
            'unit_name' => $position->organizationalUnit?->name,
            'official_name' => $assignment instanceof PositionAssignment
                ? $assignment->user->name
                : null,
        ];
    }
}
