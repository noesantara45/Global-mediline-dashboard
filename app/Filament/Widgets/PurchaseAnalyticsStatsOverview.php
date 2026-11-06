<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
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
        } else {
            $query->where('status', '!=', 'Cancelled');
        }

        return $query;
    }

    protected function getStats(): array
    {
        $query = $this->getFilteredQuery();

        $totalExpense = (float) (clone $query)->sum('total_amount');
        $totalSubtotal = (float) (clone $query)->sum('subtotal_amount');
        $totalTax = (float) (clone $query)->sum('tax_amount');
        $totalPaid = (float) (clone $query)->sum('paid_amount');
        $invoiceCount = (int) (clone $query)->count();
        $paidCount = (int) (clone $query)->where('status', 'Paid')->count();
        $activeSuppliers = (int) (clone $query)->distinct('supplier_id')->count('supplier_id');

        $unpaidQuery = (clone $query)->whereIn('status', ['Unpaid', 'Partial']);
        $unpaidCount = (int) (clone $unpaidQuery)->count();
        $unpaidDebt = (float) (clone $unpaidQuery)
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as debt')
            ->value('debt');

        $invoiceIds = (clone $query)->pluck('inbound_invoices.id');
        $totalItemsQty = $invoiceIds->isEmpty()
            ? 0
            : (int) InboundInvoiceItem::whereIn('inbound_invoice_id', $invoiceIds)->sum('quantity');

        return [
            Stat::make('Total Pembelian (Inbound)', 'Rp '.number_format($totalExpense, 0, ',', '.'))
                ->description('Sub Total Rp '.number_format($totalSubtotal, 0, ',', '.').' + PPN Rp '.number_format($totalTax, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('danger'),

            Stat::make('Sudah Dibayar ke Supplier', 'Rp '.number_format($totalPaid, 0, ',', '.'))
                ->description(number_format($paidCount, 0, ',', '.').' Faktur Lunas')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Sisa Hutang Pembelian', 'Rp '.number_format($unpaidDebt, 0, ',', '.'))
                ->description(number_format($unpaidCount, 0, ',', '.').' Faktur Belum Lunas')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unpaidDebt > 0 ? 'warning' : 'gray'),

            Stat::make('Volume Faktur & Barang', number_format($invoiceCount, 0, ',', '.').' Faktur')
                ->description(number_format($totalItemsQty, 0, ',', '.').' Qty Barang dari '.number_format($activeSuppliers, 0, ',', '.').' Supplier')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),
        ];
    }
}
