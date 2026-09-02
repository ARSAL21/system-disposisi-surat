<?php

namespace App\Reporting;

use App\Models\IncomingLetter;
use App\Models\LetterSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class ReportMetricsQuery
{
    public function __construct(
        private readonly ReportLetterQuery $letterQuery,
        private readonly ReportScopeResolver $scopeResolver,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return array{received_letters: int, processing_started: int, completed_letters: int, average_completion_hours: float|null}
     */
    public function summary(User $user, array $filters): array
    {
        $received = $this->eventQuery($user, $filters, 'RECEIVED');
        $started = $this->eventQuery($user, $filters, 'PROCESSING_STARTED');
        $completed = $this->eventQuery($user, $filters, 'COMPLETED');
        $durations = (clone $completed)
            ->select('incoming_letters.received_at')
            ->selectSub($this->letterQuery->terminalCompletionSubquery(), 'report_completed_at')
            ->cursor()
            ->map(function (IncomingLetter $letter): ?float {
                $completedAt = $letter->getAttribute('report_completed_at');

                if (! is_string($completedAt)) {
                    return null;
                }

                return $letter->received_at->diffInSeconds(CarbonImmutable::parse($completedAt), false) / 3600;
            })
            ->filter(fn (?float $duration): bool => $duration !== null && $duration >= 0);

        return [
            'received_letters' => (clone $received)->count(),
            'processing_started' => (clone $started)->count(),
            'completed_letters' => (clone $completed)->count(),
            'average_completion_hours' => $durations->isEmpty()
                ? null
                : round((float) $durations->average(), 1),
        ];
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return list<array{source: string, label: string, total: int, percent: int}>
     */
    public function sourceBreakdown(User $user, array $filters): array
    {
        $rows = $this->letterQuery->aggregate($user, $filters)
            ->join('letter_submissions', 'letter_submissions.id', '=', 'incoming_letters.letter_submission_id')
            ->selectRaw('letter_submissions.source as report_source, COUNT(DISTINCT incoming_letters.id) as aggregate_total')
            ->groupBy('letter_submissions.source')
            ->pluck('aggregate_total', 'report_source');
        $total = (int) $rows->sum();

        return array_values(collect(['ONLINE' => 'Pengajuan online', 'MANUAL' => 'Penerimaan manual'])
            ->map(fn (string $label, string $source): array => [
                'source' => $source,
                'label' => $label,
                'total' => (int) ($rows[$source] ?? 0),
                'percent' => $total === 0
                    ? 0
                    : (int) round(((int) ($rows[$source] ?? 0) / $total) * 100),
            ])
            ->all());
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return array{online_submissions: int, manual_submissions: int, converted_to_letters: int}
     */
    public function intakeFunnel(User $user, array $filters): array
    {
        [$from, $to] = $this->letterQuery->officeUtcBounds($filters['date_from'], $filters['date_to']);
        $scope = $this->scopeResolver->resolve($user);
        $query = LetterSubmission::query()
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$from, $to]);

        if ($filters['source'] !== '') {
            $query->where('source', $filters['source']);
        }

        if ($scope === null) {
            $query->whereRaw('1 = 0');
        } elseif (! $scope->hasGlobalAggregates) {
            $authorizedLetterIds = $this->letterQuery->aggregateScope($user)
                ->select('incoming_letters.id');
            $query->whereHas('incomingLetter', fn (Builder $letter): Builder => $letter
                ->whereIn('incoming_letters.id', $authorizedLetterIds));
        }

        return [
            'online_submissions' => (clone $query)->where('source', 'ONLINE')->count(),
            'manual_submissions' => (clone $query)->where('source', 'MANUAL')->count(),
            'converted_to_letters' => (clone $query)->whereHas('incomingLetter')->count(),
        ];
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return list<array{name: string, total: int, percent: int}>
     */
    public function senderBreakdown(User $user, array $filters): array
    {
        $rows = $this->letterQuery->aggregate($user, $filters)
            ->join('sender_organizations', 'sender_organizations.id', '=', 'incoming_letters.sender_organization_id')
            ->selectRaw('sender_organizations.name as sender_name, COUNT(DISTINCT incoming_letters.id) as aggregate_total')
            ->groupBy('sender_organizations.id', 'sender_organizations.name')
            ->orderByDesc('aggregate_total')
            ->orderBy('sender_organizations.name')
            ->limit(10)
            ->get();
        $largest = max(1, (int) ($rows->first()?->getAttribute('aggregate_total') ?? 0));

        return array_values($rows->map(fn (IncomingLetter $row): array => [
            'name' => (string) $row->getAttribute('sender_name'),
            'total' => (int) $row->getAttribute('aggregate_total'),
            'percent' => (int) round(((int) $row->getAttribute('aggregate_total') / $largest) * 100),
        ])->all());
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return list<array{label: string, received: int, processing_started: int, completed: int}>
     */
    public function trend(User $user, array $filters): array
    {
        $timezone = (string) config('letter-activity.timezone');
        $from = CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_from'], $timezone);
        $to = CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_to'], $timezone);
        $interval = $from->diffInDays($to) <= 31
            ? 'day'
            : ($from->diffInDays($to) <= 180 ? 'week' : 'month');
        $buckets = $this->emptyBuckets($from, $to, $interval);

        $received = $this->eventQuery($user, $filters, 'RECEIVED')
            ->pluck('received_at');
        $started = $this->eventQuery($user, $filters, 'PROCESSING_STARTED')
            ->selectSub($this->letterQuery->processingStartedSubquery(), 'event_at')
            ->pluck('event_at');
        $completed = $this->eventQuery($user, $filters, 'COMPLETED')
            ->selectSub($this->letterQuery->terminalCompletionSubquery(), 'event_at')
            ->pluck('event_at');

        $this->addEvents($buckets, $received->all(), 'received', $interval, $timezone);
        $this->addEvents($buckets, $started->all(), 'processing_started', $interval, $timezone);
        $this->addEvents($buckets, $completed->all(), 'completed', $interval, $timezone);

        return array_values($buckets);
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return Builder<IncomingLetter>
     */
    private function eventQuery(User $user, array $filters, string $event): Builder
    {
        return $this->letterQuery->aggregate($user, [...$filters, 'event' => $event]);
    }

    /**
     * @return array<string, array{label: string, received: int, processing_started: int, completed: int}>
     */
    private function emptyBuckets(CarbonImmutable $from, CarbonImmutable $to, string $interval): array
    {
        $buckets = [];
        $cursor = match ($interval) {
            'day' => $from->startOfDay(),
            'week' => $from->startOfWeek(),
            default => $from->startOfMonth(),
        };

        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $this->bucketKey($cursor, $interval);
            $buckets[$key] = [
                'label' => $this->bucketLabel($cursor, $interval),
                'received' => 0,
                'processing_started' => 0,
                'completed' => 0,
            ];
            $cursor = match ($interval) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $buckets;
    }

    /**
     * @param  array<string, array{label: string, received: int, processing_started: int, completed: int}>  $buckets
     * @param  array<int, mixed>  $events
     */
    private function addEvents(array &$buckets, array $events, string $metric, string $interval, string $timezone): void
    {
        foreach ($events as $event) {
            if (! is_string($event) && ! $event instanceof CarbonInterface) {
                continue;
            }

            $date = CarbonImmutable::parse($event)->setTimezone($timezone);
            $key = $this->bucketKey($date, $interval);

            if (! array_key_exists($key, $buckets)) {
                continue;
            }

            match ($metric) {
                'received' => $buckets[$key]['received']++,
                'processing_started' => $buckets[$key]['processing_started']++,
                'completed' => $buckets[$key]['completed']++,
                default => null,
            };
        }
    }

    private function bucketKey(CarbonImmutable $date, string $interval): string
    {
        return match ($interval) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    private function bucketLabel(CarbonImmutable $date, string $interval): string
    {
        return match ($interval) {
            'day' => $date->translatedFormat('j M'),
            'week' => 'Pekan '.$date->translatedFormat('j M'),
            default => $date->translatedFormat('M Y'),
        };
    }
}
