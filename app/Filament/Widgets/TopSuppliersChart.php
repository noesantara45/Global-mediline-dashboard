<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TopSuppliersChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected static ?string $heading = 'Top Supplier Pengadaan Terbesar';

    public ?string $filter = '5';

    public function getDescription(): ?string
    {
        return 'Peringkat vendor & principal berdasarkan total nilai pengadaan barang.';
    }

    protected function getFilters(): ?array
    {
        return [
            '5' => 'Top 5 Supplier',
            '10' => 'Top 10 Supplier',
        ];
    }

    protected function getFilteredQuery(): Builder
    {
        $query = InboundInvoice::query();

        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $supplierId = $this->filters['supplier_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        if ($startDate) {
            $query->whereDate('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
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
            ->join('suppliers', 'inbound_invoices.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.name as supplier_name, SUM(inbound_invoices.total_amount) as total_expense')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_expense')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Total Pembelian (Rp)',
                        'data' => [0],
                        'backgroundColor' => '#E5E7EB',
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => ['Belum ada transaksi pengadaan'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembelian (Rp)',
                    'data' => $results->pluck('total_expense')->map(fn ($val): float => (float) $val)->toArray(),
                    'backgroundColor' => '#F97316',
                    'hoverBackgroundColor' => '#EA580C',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $results->pluck('supplier_name')->toArray(),
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
