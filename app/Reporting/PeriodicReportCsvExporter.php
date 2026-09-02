<?php

namespace App\Reporting;

use App\Models\User;
use Illuminate\Support\Collection;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PeriodicReportCsvExporter
{
    public function __construct(
        private readonly ReportLetterQuery $letterQuery,
        private readonly ReportMetricsQuery $metricsQuery,
        private readonly ReportOrganizationGraphQuery $graphQuery,
        private readonly ReportScopeResolver $scopeResolver,
        private readonly PeriodicReportPresenter $presenter,
        private readonly CsvCellSanitizer $sanitizer,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     */
    public function summary(User $user, array $filters): StreamedResponse
    {
        $filename = "ringkasan-laporan-{$filters['date_from']}-{$filters['date_to']}.csv";

        return $this->response($filename, function ($stream) use ($user, $filters): void {
            $scope = $this->scopeResolver->resolve($user);

            if ($scope === null) {
                throw new LogicException('Authorized report exports require a valid Position scope.');
            }

            $summary = $this->metricsQuery->summary($user, $filters);
            $trend = $this->metricsQuery->trend($user, $filters);
            $sources = $this->metricsQuery->sourceBreakdown($user, $filters);
            $senders = $this->metricsQuery->senderBreakdown($user, $filters);
            $intake = $this->metricsQuery->intakeFunnel($user, $filters);
            $graph = $this->graphQuery->build($user, $filters);

            $this->row($stream, ['LAPORAN PERIODIK SURAT']);
            $this->row($stream, ['Periode', $filters['date_from'], $filters['date_to']]);
            $this->row($stream, ['Cakupan', $scope->label]);
            $this->row($stream, []);
            $this->row($stream, ['KPI']);
            $this->row($stream, ['Surat diterima', 'Mulai diproses', 'Surat selesai', 'Rata-rata penyelesaian (jam)']);
            $this->row($stream, [
                $summary['received_letters'],
                $summary['processing_started'],
                $summary['completed_letters'],
                $summary['average_completion_hours'],
            ]);
            $this->row($stream, []);
            $this->row($stream, ['SUBMISSION']);
            $this->row($stream, ['Online', 'Manual', 'Menjadi surat masuk']);
            $this->row($stream, [
                $intake['online_submissions'],
                $intake['manual_submissions'],
                $intake['converted_to_letters'],
            ]);
            $this->row($stream, []);
            $this->row($stream, ['TREN']);
            $this->row($stream, ['Interval', 'Diterima', 'Mulai diproses', 'Selesai']);
            foreach ($trend as $point) {
                $this->row($stream, [
                    $point['label'],
                    $point['received'],
                    $point['processing_started'],
                    $point['completed'],
                ]);
            }

            $this->row($stream, []);
            $this->row($stream, ['SUMBER SURAT']);
            $this->row($stream, ['Sumber', 'Jumlah', 'Persentase']);
            foreach ($sources as $source) {
                $this->row($stream, [$source['label'], $source['total'], $source['percent']]);
            }

            $this->row($stream, []);
            $this->row($stream, ['INSTANSI PENGIRIM']);
            $this->row($stream, ['Instansi', 'Jumlah']);
            foreach ($senders as $sender) {
                $this->row($stream, [$sender['name'], $sender['total']]);
            }

            $this->row($stream, []);
            $this->row($stream, ['PERFORMA JABATAN']);
            $this->row($stream, ['Level', 'Kode', 'Jabatan', 'Pejabat', 'Ditugaskan', 'Menunggu', 'Dikerjakan', 'Selesai', 'Persentase selesai', 'Rata-rata durasi (jam)']);
            foreach ($this->performanceRows($graph['executives']) as $performance) {
                $this->row($stream, $performance);
            }
        });
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     */
    public function letters(User $user, array $filters): StreamedResponse
    {
        $filename = "daftar-surat-{$filters['date_from']}-{$filters['date_to']}.csv";

        return $this->response($filename, function ($stream) use ($user, $filters): void {
            $this->row($stream, [
                'Nomor agenda',
                'Tanggal diterima',
                'Sumber',
                'Instansi pengirim',
                'Perihal',
                'Status',
                'Waktu mulai proses',
                'Waktu selesai',
                'Durasi (jam)',
                'Cabang menunggu',
                'Cabang dikerjakan',
                'Cabang selesai',
                'Total cabang',
            ]);

            $this->letterQuery->list($user, $filters)
                ->reorder('incoming_letters.id')
                ->chunkById(500, function (Collection $letters) use ($stream, $user): void {
                    foreach ($this->presenter->letters($letters, $user) as $letter) {
                        $progress = $letter['branch_progress'];
                        $this->row($stream, [
                            $letter['agenda_number'],
                            $letter['received_at'],
                            $letter['source'],
                            $letter['sender_organization_name'],
                            $letter['subject'],
                            $letter['status'],
                            $letter['processing_started_at'],
                            $letter['completed_at'],
                            $letter['turnaround_hours'],
                            $progress['pending'],
                            $progress['in_progress'],
                            $progress['completed'],
                            $progress['total'],
                        ]);
                    }
                }, 'incoming_letters.id', 'id');
        });
    }

    /** @param callable(resource): void $callback */
    private function response(string $filename, callable $callback): StreamedResponse
    {
        return new StreamedResponse(function () use ($callback): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            $callback($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  resource  $stream
     * @param  list<string|int|float|null>  $cells
     */
    private function row($stream, array $cells): void
    {
        fputcsv($stream, array_map(
            fn (string|int|float|null $cell): string|int|float => $this->sanitizer->sanitize($cell),
            $cells,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $executives
     * @return list<list<string|int|float|null>>
     */
    private function performanceRows(array $executives): array
    {
        $positions = [];

        foreach ($executives as $executive) {
            foreach ($executive['children'] ?? [] as $assistant) {
                $this->mergePerformance($positions, 'Asisten', $assistant);

                foreach ($assistant['children'] ?? [] as $section) {
                    $this->mergePerformance($positions, 'Kepala Bagian', $section);
                }
            }
        }

        return array_values(array_map(function (array $item): array {
            $progress = $item['progress'];
            $total = $progress['pending'] + $progress['in_progress'] + $progress['completed'];

            return [
                $item['level'],
                $item['position']['code'],
                $item['position']['name'],
                $item['position']['official_name'],
                $total,
                $progress['pending'],
                $progress['in_progress'],
                $progress['completed'],
                $total === 0 ? 0 : (int) floor(($progress['completed'] / $total) * 100),
                $progress['completed'] === 0
                    ? null
                    : round($item['completion_hours_sum'] / $progress['completed'], 1),
            ];
        }, $positions));
    }

    /**
     * @param  array<string, array<string, mixed>>  $positions
     * @param  array<string, mixed>  $node
     */
    private function mergePerformance(array &$positions, string $level, array $node): void
    {
        $code = (string) $node['recipient_position']['code'];
        $positions[$code] ??= [
            'level' => $level,
            'position' => $node['recipient_position'],
            'progress' => ['pending' => 0, 'in_progress' => 0, 'completed' => 0],
            'completion_hours_sum' => 0.0,
        ];

        if (is_int($node['average_completion_hours']) || is_float($node['average_completion_hours'])) {
            $positions[$code]['completion_hours_sum'] += $node['average_completion_hours']
                * (int) $node['progress']['completed'];
        }

        foreach (['pending', 'in_progress', 'completed'] as $status) {
            $positions[$code]['progress'][$status] += (int) $node['progress'][$status];
        }
    }
}
