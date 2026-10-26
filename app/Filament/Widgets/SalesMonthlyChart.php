<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SalesMonthlyChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Tren Omzet Penjualan Bulanan (Tahun Ini)';

    protected function getData(): array
    {
        $data = Trend::model(OutboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => '#10B981',
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
