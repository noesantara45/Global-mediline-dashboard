<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\GrafikPenjualan;
use App\Filament\Pages\LaporanPembelian;
use App\Filament\Pages\LaporanPenjualan;
use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $outboundStats = OutboundInvoice::query()
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status != 'Cancelled' AND issue_date >= ? AND issue_date <= ? THEN total_amount ELSE 0 END), 0) as month_revenue,
                 COALESCE(SUM(CASE WHEN status != 'Cancelled' AND issue_date >= ? AND issue_date <= ? THEN 1 ELSE 0 END), 0) as month_count,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as receivable_remaining,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND due_date < ? THEN 1 ELSE 0 END), 0) as overdue_count",
                [$monthStart, $monthEnd, $monthStart, $monthEnd, $today]
            )
            ->first();

        $outboundRevenue = (float) ($outboundStats?->month_revenue ?? 0);
        $outboundCount = (int) ($outboundStats?->month_count ?? 0);
        $piutangKlien = (float) ($outboundStats?->receivable_remaining ?? 0);
        $overdueOutboundCount = (int) ($outboundStats?->overdue_count ?? 0);

        $inboundStats = InboundInvoice::query()
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status != 'Cancelled' AND issue_date >= ? AND issue_date <= ? THEN total_amount ELSE 0 END), 0) as month_expense,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as payable_remaining,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') THEN 1 ELSE 0 END), 0) as unpaid_count",
                [$monthStart, $monthEnd]
            )
            ->first();

        $inboundExpense = (float) ($inboundStats?->month_expense ?? 0);
        $hutangSupplier = (float) ($inboundStats?->payable_remaining ?? 0);
        $unpaidInboundCount = (int) ($inboundStats?->unpaid_count ?? 0);

        $marginKas = $outboundRevenue - $inboundExpense;

        // 6-month mini sparkline trends (2 aggregate queries, safe against month-end overflow)
        $sparklineStart = now()->startOfMonth()->subMonthsNoOverflow(5);
        $sparklineEnd = now()->endOfMonth();

        $salesTrend = Trend::query(OutboundInvoice::query()->where('status', '!=', 'Cancelled'))
            ->dateColumn('issue_date')
            ->between(start: $sparklineStart, end: $sparklineEnd)
            ->perMonth()
            ->sum('total_amount');

        $purchasesByMonth = Trend::query(InboundInvoice::query()->where('status', '!=', 'Cancelled'))
            ->dateColumn('issue_date')
            ->between(start: $sparklineStart, end: $sparklineEnd)
            ->perMonth()
            ->sum('total_amount')
            ->keyBy('date');

        $salesSparkline = $salesTrend
            ->map(fn (TrendValue $value): float => round(((float) $value->aggregate) / 1_000_000, 2))
            ->all();

        $marginSparkline = $salesTrend
            ->map(function (TrendValue $value) use ($purchasesByMonth): float {
                $monthSales = (float) $value->aggregate;
                $monthPurchases = (float) ($purchasesByMonth->get($value->date)?->aggregate ?? 0);

                return round(($monthSales - $monthPurchases) / 1_000_000, 2);
            })
            ->all();

        $receivableUrl = $overdueOutboundCount > 0
            ? LaporanPenjualan::getUrl(['tableFilters' => ['due_condition' => ['condition' => 'overdue']]])
            : LaporanPenjualan::getUrl(['tableFilters' => ['status' => ['value' => 'Unpaid']]]);

        return [
            Stat::make('Omset Penjualan (Bulan Ini)', 'Rp '.number_format($outboundRevenue, 0, ',', '.'))
                ->description($outboundCount.' Faktur Aktif • Lihat Laporan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($salesSparkline)
                ->color('success')
                ->url(LaporanPenjualan::getUrl()),

            Stat::make('Total Piutang Klien', 'Rp '.number_format($piutangKlien, 0, ',', '.'))
                ->description(
                    $overdueOutboundCount > 0
                        ? $overdueOutboundCount.' Faktur Lewat Jatuh Tempo'
                        : 'Seluruh tagihan masih dalam tempo'
                )
                ->descriptionIcon($overdueOutboundCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
                ->color($overdueOutboundCount > 0 ? 'danger' : 'warning')
                ->url($receivableUrl),

            Stat::make('Hutang ke Supplier', 'Rp '.number_format($hutangSupplier, 0, ',', '.'))
                ->description($unpaidInboundCount.' Faktur Inbound Belum Lunas')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->url(LaporanPembelian::getUrl(['tableFilters' => ['status' => ['value' => 'Unpaid']]])),

            Stat::make('Estimasi Margin Kas', 'Rp '.number_format($marginKas, 0, ',', '.'))
                ->description('Penjualan - Pembelian Bulan Ini')
                ->descriptionIcon($marginKas >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-arrow-trending-down')
                ->chart($marginSparkline)
                ->color($marginKas >= 0 ? 'primary' : 'danger')
                ->url(GrafikPenjualan::getUrl()),
        ];
    }
}
