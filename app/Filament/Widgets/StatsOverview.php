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
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 1. Omset Penjualan Bulan Ini (exclude Cancelled)
        $activeOutboundThisMonth = OutboundInvoice::query()
            ->where('status', '!=', 'Cancelled')
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear);

        $outboundRevenue = (float) (clone $activeOutboundThisMonth)->sum('total_amount');
        $outboundCount = (int) (clone $activeOutboundThisMonth)->count();

        // 2. Piutang Klien (Unpaid & Partial)
        $unpaidOutboundQuery = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial']);
        $unpaidOutboundTotal = (float) (clone $unpaidOutboundQuery)->sum('total_amount');
        $unpaidOutboundPaid = (float) (clone $unpaidOutboundQuery)->sum('paid_amount');
        $piutangKlien = max(0, $unpaidOutboundTotal - $unpaidOutboundPaid);

        $overdueOutboundCount = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        // 3. Hutang ke Supplier (Unpaid & Partial)
        $unpaidInboundQuery = InboundInvoice::whereIn('status', ['Unpaid', 'Partial']);
        $unpaidInboundTotal = (float) (clone $unpaidInboundQuery)->sum('total_amount');
        $unpaidInboundPaid = (float) (clone $unpaidInboundQuery)->sum('paid_amount');
        $hutangSupplier = max(0, $unpaidInboundTotal - $unpaidInboundPaid);
        $unpaidInboundCount = (int) (clone $unpaidInboundQuery)->count();

        // 4. Pengeluaran Pembelian & Margin Kas Bersih Bulan Ini (exclude Cancelled)
        $inboundExpense = (float) InboundInvoice::query()
            ->where('status', '!=', 'Cancelled')
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total_amount');

        $marginKas = $outboundRevenue - $inboundExpense;

        // 6-month mini sparkline trends (2 aggregate queries, safe against month-end overflow)
        $sparklineStart = now()->startOfMonth()->subMonths(5);
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
