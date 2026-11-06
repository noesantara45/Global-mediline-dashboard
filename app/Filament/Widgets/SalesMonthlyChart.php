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
        }

        return $query;
    }

    protected function getData(): array
    {
        $startDateStr = $this->filters['start_date'] ?? null;
        $endDateStr = $this->filters['end_date'] ?? null;

        $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : now()->startOfYear();
        $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : now()->endOfYear();

        $diffInDays = $start->diffInDays($end);
        $isDaily = ($diffInDays <= 31);

        $trend = Trend::query($this->getFilteredQuery())
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
