<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;

class SalesMonthlyChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected static ?string $heading = 'Tren Omzet Penjualan';

    public function getDescription(): ?string
    {
        return 'Pergerakan nilai omzet penjualan sesuai filter periode dan divisi aktif.';
    }

    protected function getFilteredQuery(): Builder
    {
        $query = OutboundInvoice::query();

        $division = $this->filters['division'] ?? null;
        $clientType = $this->filters['client_type'] ?? null;
        $clientId = $this->filters['client_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        if ($division) {
            $query->where(function (Builder $subQuery) use ($division): void {
                $subQuery->where('division', $division)
                    ->orWhereHas('items.product', fn (Builder $pq): Builder => $pq->where('division', $division));
            });
        }

        if ($clientType) {
            $query->whereHas('client', function (Builder $q) use ($clientType): void {
                $q->where('type', $clientType);
            });
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
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

        $minDate = null;
        $maxDate = null;

        if (! $startDateStr || ! $endDateStr) {
            $dateRange = (clone $baseQuery)
                ->selectRaw('MIN(issue_date) as min_date, MAX(issue_date) as max_date')
                ->toBase()
                ->first();
            $minDate = $dateRange?->min_date;
            $maxDate = $dateRange?->max_date;
        }

        if (! $startDateStr && ! $endDateStr) {
            if ($minDate && $maxDate) {
                $start = Carbon::parse($minDate)->startOfMonth();
                $end = Carbon::parse($maxDate)->endOfMonth();

                if ($start->diffInMonths($end) < 5) {
                    $start = (clone $end)->startOfMonth()->subMonthsNoOverflow(5);
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

        $trend = Trend::query($baseQuery)
            ->dateColumn('issue_date')
            ->between(start: $start, end: $end);

        $data = $isDaily
            ? $trend->perDay()->sum('total_amount')
            : $trend->perMonth()->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data->map(fn (TrendValue $value): float => (float) $value->aggregate),
                    'backgroundColor' => 'rgba(45, 138, 86, 0.16)',
                    'borderColor' => '#2D8A56',
                    'pointBackgroundColor' => '#1F4E34',
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
