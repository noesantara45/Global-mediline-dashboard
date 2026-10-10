<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class IncomeExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pemasukan vs Pengeluaran (Tahun Ini)';

    protected function getData(): array
    {
        $outboundData = \Flowframe\Trend\Trend::model(\App\Models\OutboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');
            
        $inboundData = \Flowframe\Trend\Trend::model(\App\Models\InboundInvoice::class)
            ->dateColumn('issue_date')
            ->between(start: now()->startOfYear(), end: now()->endOfYear())
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Outbound)',
                    'data' => $outboundData->map(fn (\Flowframe\Trend\TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#2E8B57', // Green
                ],
                [
                    'label' => 'Pengeluaran (Inbound)',
                    'data' => $inboundData->map(fn (\Flowframe\Trend\TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#E3342F', // Red
                ],
            ],
            'labels' => $outboundData->map(fn (\Flowframe\Trend\TrendValue $value) => \Carbon\Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
