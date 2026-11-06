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

    protected static ?string $heading = 'Tren Pembelian Inbound';

    public function getDescription(): ?string
    {
        return 'Rekap nilai pembelian dan pembayaran dari Inbound Invoices sesuai filter aktif.';
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
        } else {
            $query->where('status', '!=', 'Cancelled');
        }

        return $query;
    }

    protected function getData(): array
    {
        $baseQuery = $this->getFilteredQuery();
        $startDateStr = $this->filters['start_date'] ?? null;
        $endDateStr = $this->filters['end_date'] ?? null;

        $minDate = (clone $baseQuery)->min('issue_date');
        $maxDate = (clone $baseQuery)->max('issue_date');

        if (! $startDateStr && ! $endDateStr) {
            if ($minDate && $maxDate) {
                $start = Carbon::parse($minDate)->startOfMonth();
                $end = Carbon::parse($maxDate)->endOfMonth();

                if ($start->diffInMonths($end) < 5) {
                    $start = (clone $end)->subMonths(5)->startOfMonth();
                }
            } else {
                $start = now()->startOfYear();
                $end = now()->endOfYear();
            }
        } else {
            $start = $startDateStr
                ? Carbon::parse($startDateStr)->startOfDay()
                : ($minDate ? Carbon::parse($minDate)->startOfMonth() : now()->startOfYear());
            $end = $endDateStr
                ? Carbon::parse($endDateStr)->endOfDay()
                : ($maxDate ? Carbon::parse($maxDate)->endOfMonth() : now()->endOfYear());
        }

        if ($start->gt($end)) {
            $end = (clone $start)->endOfMonth();
        }

        $diffInDays = $start->diffInDays($end);
        $isDaily = ($diffInDays <= 31);

        $purchaseTrend = Trend::query(clone $baseQuery)
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end);

        $purchaseData = $isDaily
            ? $purchaseTrend->perDay()->sum('total_amount')
            : $purchaseTrend->perMonth()->sum('total_amount');

        $paidTrend = Trend::query(clone $baseQuery)
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end);

        $paidData = $isDaily
            ? $paidTrend->perDay()->sum('paid_amount')
            : $paidTrend->perMonth()->sum('paid_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembelian (Rp)',
                    'data' => $purchaseData->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
                    'borderColor' => '#EF4444',
                    'pointBackgroundColor' => '#EF4444',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverRadius' => 6,
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Sudah Dibayar (Rp)',
                    'data' => $paidData->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'borderColor' => '#10B981',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverRadius' => 5,
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $purchaseData->map(function (TrendValue $value) use ($isDaily): string {
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
                    'display' => true,
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
