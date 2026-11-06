<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class IncomeExpenseChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 2,
        'xl' => 4,
    ];

    protected static ?string $heading = 'Tren Penjualan vs Pembelian';

    public ?string $filter = 'this_year';

    public function getDescription(): ?string
    {
        return 'Perbandingan total faktur penjualan (Outbound) dan pembelian (Inbound) aktif per bulan.';
    }

    protected function getFilters(): ?array
    {
        $currentYear = now()->year;

        return [
            'this_year' => 'Tahun '.$currentYear,
            'last_year' => 'Tahun '.($currentYear - 1),
        ];
    }

    protected function getData(): array
    {
        $targetYear = $this->filter === 'last_year' ? now()->startOfYear()->subYear() : now()->startOfYear();
        $start = $targetYear->copy()->startOfYear();
        $end = $targetYear->copy()->endOfYear();

        $outboundData = Trend::query(OutboundInvoice::query()->where('status', '!=', 'Cancelled'))
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end)
            ->perMonth()
            ->sum('total_amount');

        $inboundData = Trend::query(InboundInvoice::query()->where('status', '!=', 'Cancelled'))
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end)
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan (Outbound)',
                    'data' => $outboundData->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => '#2D8A56',
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Pembelian (Inbound)',
                    'data' => $inboundData->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => '#F59E0B',
                    'borderRadius' => 5,
                ],
            ],
            'labels' => $outboundData->map(fn (TrendValue $value): string => Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 14,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
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
