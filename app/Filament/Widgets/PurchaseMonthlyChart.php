<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PurchaseMonthlyChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Tren Pengeluaran Pembelian Supplier (Tahun Ini)';

    protected function getData(): array
    {
        $data = Trend::model(InboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembelian (Rp)',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => '#EF4444',
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
