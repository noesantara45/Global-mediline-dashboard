<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Filament\Widgets\ChartWidget;

class InvoiceStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Status Tagihan';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    public ?string $filter = 'amount';

    public function getDescription(): ?string
    {
        return 'Perbandingan tagihan lunas, dalam tempo, dan lewat jatuh tempo.';
    }

    protected function getFilters(): ?array
    {
        return [
            'amount' => 'Nominal Rupiah (Rp)',
            'count' => 'Jumlah Faktur',
        ];
    }

    protected function getData(): array
    {
        $isCount = ($this->filter === 'count');
        $today = now()->toDateString();

        if ($isCount) {
            $paid = OutboundInvoice::where('status', 'Paid')->count();

            $inTempo = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
                ->whereDate('due_date', '>=', $today)
                ->count();

            $overdue = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
                ->whereDate('due_date', '<', $today)
                ->count();
        } else {
            $paid = (float) OutboundInvoice::where('status', 'Paid')->sum('total_amount');

            $inTempoInvoices = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
                ->whereDate('due_date', '>=', $today)
                ->get();
            $inTempo = (float) $inTempoInvoices->sum(fn (OutboundInvoice $inv): float => max(0, (float) $inv->total_amount - (float) $inv->paid_amount));

            $overdueInvoices = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
                ->whereDate('due_date', '<', $today)
                ->get();
            $overdue = (float) $overdueInvoices->sum(fn (OutboundInvoice $inv): float => max(0, (float) $inv->total_amount - (float) $inv->paid_amount));
        }

        return [
            'datasets' => [
                [
                    'label' => $isCount ? 'Jumlah Faktur' : 'Nominal Tagihan',
                    'data' => [$paid, $inTempo, $overdue],
                    'backgroundColor' => [
                        '#10B981', // Emerald - Lunas
                        '#3B82F6', // Blue - Dalam Tempo
                        '#EF4444', // Red - Lewat Jatuh Tempo
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => [
                'Lunas',
                'Dalam Tempo',
                'Lewat Jatuh Tempo',
            ],
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
                        'padding' => 16,
                    ],
                ],
            ],
            'cutout' => '65%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
