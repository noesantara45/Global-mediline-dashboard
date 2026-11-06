<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SalesAnalyticsStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getFilteredQuery(): Builder
    {
        $query = OutboundInvoice::query();

        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $division = $this->filters['division'] ?? null;
        $clientType = $this->filters['client_type'] ?? null;
        $clientId = $this->filters['client_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        if ($startDate) {
            $query->whereDate('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

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

    protected function getStats(): array
    {
        $query = $this->getFilteredQuery();

        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $invoiceCount = (int) (clone $query)->count();
        $averageOrder = $invoiceCount > 0 ? ($totalRevenue / $invoiceCount) : 0;
        $activeClients = (int) (clone $query)->distinct('client_id')->count('client_id');

        return [
            Stat::make('Total Omzet Penjualan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description('Total pendapatan faktur periode ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Rata-rata Order (AOV)', 'Rp '.number_format($averageOrder, 0, ',', '.'))
                ->description('Nilai rata-rata per transaksi faktur')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Volume Transaksi', number_format($invoiceCount, 0, ',', '.').' Faktur')
                ->description('Jumlah faktur penjualan terbit')
                ->descriptionIcon('heroicon-m-document-chart-bar')
                ->color('primary'),

            Stat::make('Klien Aktif', number_format($activeClients, 0, ',', '.').' Customer')
                ->description('RS / Klinik yang bertransaksi')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning'),
        ];
    }
}
