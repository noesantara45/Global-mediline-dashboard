<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;

class PurchaseMonthlyChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected static ?string $heading = 'Tren Pengeluaran Pembelian';

    public function getDescription(): ?string
    {
        return 'Pergerakan nilai belanja barang ke supplier sesuai filter periode aktif.';
    }

    protected function getFilteredQuery(): Builder
    {
        $query = InboundInvoice::query();

        $supplierId = $this->filters['supplier_id'] ?? null;
        $status = $this->filters['status'] ?? null;

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
        $startDateStr = $this->filters['start_date'] ?? null;
        $endDateStr = $this->filters['end_date'] ?? null;

        $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : now()->startOfYear();
        $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : now()->endOfYear();

        $diffInDays = $start->diffInDays($end);
        $isDaily = ($diffInDays <= 31);

        $trend = Trend::query($this->getFilteredQuery())
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end);

        $data = $isDaily
            ? $trend->perDay()->sum('total_amount')
            : $trend->perMonth()->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembelian (Rp)',
                    'data' => $data->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
                    'borderColor' => '#EF4444',
                    'pointBackgroundColor' => '#EF4444',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverRadius' => 6,
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $data->map(function (TrendValue $value) use ($isDaily): string {
                return $isDaily
                    ? Carbon::parse($value->date)->translatedFormat('d M')
                    : Carbon::parse($value->date)->translatedFormat('M Y');
            }),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
