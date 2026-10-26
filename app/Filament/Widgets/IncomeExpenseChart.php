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
    protected static ?string $heading = 'Grafik Pemasukan vs Pengeluaran (Tahun Ini)';

    protected function getData(): array
    {
        $outboundData = Trend::model(OutboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');

        $inboundData = Trend::model(InboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Outbound)',
                    'data' => $outboundData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#2E8B57', // Green
                ],
                [
                    'label' => 'Pengeluaran (Inbound)',
                    'data' => $inboundData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#E3342F', // Red
                ],
            ],
            'labels' => $outboundData->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
