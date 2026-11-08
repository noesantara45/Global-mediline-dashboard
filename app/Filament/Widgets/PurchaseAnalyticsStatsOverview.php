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

        $aggregates = (clone $query)
            ->selectRaw(
                "COALESCE(SUM(total_amount), 0) as total_expense,
                 COALESCE(SUM(subtotal_amount), 0) as total_subtotal,
                 COALESCE(SUM(tax_amount), 0) as total_tax,
                 COALESCE(SUM(paid_amount), 0) as total_paid,
                 COUNT(*) as invoice_count,
                 COALESCE(SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END), 0) as paid_count,
                 COUNT(DISTINCT supplier_id) as active_suppliers,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') THEN 1 ELSE 0 END), 0) as unpaid_count,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as unpaid_debt"
            )
            ->first();

        $totalExpense = (float) ($aggregates?->total_expense ?? 0);
        $totalSubtotal = (float) ($aggregates?->total_subtotal ?? 0);
        $totalTax = (float) ($aggregates?->total_tax ?? 0);
        $totalPaid = (float) ($aggregates?->total_paid ?? 0);
        $invoiceCount = (int) ($aggregates?->invoice_count ?? 0);
        $paidCount = (int) ($aggregates?->paid_count ?? 0);
        $activeSuppliers = (int) ($aggregates?->active_suppliers ?? 0);
        $unpaidCount = (int) ($aggregates?->unpaid_count ?? 0);
        $unpaidDebt = (float) ($aggregates?->unpaid_debt ?? 0);

        $totalItemsQty = $invoiceCount === 0
            ? 0
            : (int) InboundInvoiceItem::query()
                ->whereIn('inbound_invoice_id', (clone $query)->select('inbound_invoices.id'))
                ->sum('quantity');

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
