<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PurchaseAnalyticsStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getFilteredQuery(): Builder
    {
        $query = InboundInvoice::query();

        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $supplierId = $this->filters['supplier_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        if ($startDate) {
            $query->whereDate('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    protected function getStats(): array
    {
        $query = $this->getFilteredQuery();

        $totalExpense = (float) (clone $query)->sum('total_amount');
        $invoiceCount = (int) (clone $query)->count();
        $averagePurchase = $invoiceCount > 0 ? ($totalExpense / $invoiceCount) : 0;
        $activeSuppliers = (int) (clone $query)->distinct('supplier_id')->count('supplier_id');

        return [
            Stat::make('Total Pengeluaran Pembelian', 'Rp '.number_format($totalExpense, 0, ',', '.'))
                ->description('Total biaya pengadaan dari supplier')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('danger'),

            Stat::make('Rata-rata Order Pengadaan', 'Rp '.number_format($averagePurchase, 0, ',', '.'))
                ->description('Nilai rata-rata per faktur supplier')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Volume Faktur Masuk', number_format($invoiceCount, 0, ',', '.').' Faktur')
                ->description('Jumlah faktur pembelian masuk')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('primary'),

            Stat::make('Supplier Aktif', number_format($activeSuppliers, 0, ',', '.').' Vendor')
                ->description('Principal / supplier yang memasok')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
