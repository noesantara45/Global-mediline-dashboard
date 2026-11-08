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
            $stats = OutboundInvoice::query()
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END), 0) as paid_count,
                     COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND due_date >= ? THEN 1 ELSE 0 END), 0) as in_tempo_count,
                     COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND due_date < ? THEN 1 ELSE 0 END), 0) as overdue_count",
                    [$today, $today]
                )
                ->first();

            $paid = (int) ($stats?->paid_count ?? 0);
            $inTempo = (int) ($stats?->in_tempo_count ?? 0);
            $overdue = (int) ($stats?->overdue_count ?? 0);
        } else {
            $stats = OutboundInvoice::query()
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END), 0) as paid_sum,
                     COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND due_date >= ? AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as in_tempo_sum,
                     COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND due_date < ? AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as overdue_sum",
                    [$today, $today]
                )
                ->first();

            $paid = (float) ($stats?->paid_sum ?? 0);
            $inTempo = (float) ($stats?->in_tempo_sum ?? 0);
            $overdue = (float) ($stats?->overdue_sum ?? 0);
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
