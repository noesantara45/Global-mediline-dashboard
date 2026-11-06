<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Filament\Widgets\ChartWidget;

class InvoiceStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Status Tagihan';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 2,
        'xl' => 2,
    ];

    public ?string $filter = 'amount';

    public function getDescription(): ?string
    {
        return 'Lunas, dalam tempo & lewat tempo.';
    }

    protected function getFilters(): ?array
    {
        return [
            'amount' => 'Nominal (Rp)',
            'count' => 'Jml Faktur',
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
                        '#2D8A56', // Medical Emerald - Lunas
                        '#84C038', // Fresh Lime - Dalam Tempo
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
                        'padding' => 14,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
            'cutout' => '64%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
