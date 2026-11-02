<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TopClientsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected static ?string $heading = 'Top Klien / RS Terbesar';

    public ?string $filter = '5';

    public function getDescription(): ?string
    {
        return 'Peringkat Rumah Sakit & Klinik berdasarkan total nilai omzet belanja.';
    }

    protected function getFilters(): ?array
    {
        return [
            '5' => 'Top 5 Klien',
            '10' => 'Top 10 Klien',
        ];
    }

    protected function getFilteredQuery(): Builder
    {
        $query = OutboundInvoice::query();

        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $clientType = $this->filters['client_type'] ?? null;
        $clientId = $this->filters['client_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        if ($startDate) {
            $query->whereDate('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        if ($clientType) {
            $query->whereHas('client', function (Builder $q) use ($clientType): void {
                $q->where('type', $clientType);
            });
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    protected function getData(): array
    {
        $limit = (int) ($this->filter ?? 5);

        $results = $this->getFilteredQuery()
            ->join('clients', 'outbound_invoices.client_id', '=', 'clients.id')
            ->selectRaw('clients.name as client_name, SUM(outbound_invoices.total_amount) as total_omzet')
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('total_omzet')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Total Omzet (Rp)',
                        'data' => [0],
                        'backgroundColor' => '#E5E7EB',
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => ['Belum ada transaksi'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Omzet (Rp)',
                    'data' => $results->pluck('total_omzet')->map(fn ($val): float => (float) $val)->toArray(),
                    'backgroundColor' => '#3B82F6',
                    'hoverBackgroundColor' => '#2563EB',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $results->pluck('client_name')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
