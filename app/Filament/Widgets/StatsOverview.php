<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 1. Omset Penjualan Bulan Ini
        $outboundRevenue = OutboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total_amount');

        $outboundCount = OutboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->count();

        // 2. Piutang Klien (Unpaid & Partial)
        $unpaidOutboundQuery = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial']);
        $unpaidOutboundTotal = (float) $unpaidOutboundQuery->sum('total_amount');
        $unpaidOutboundPaid = (float) $unpaidOutboundQuery->sum('paid_amount');
        $piutangKlien = max(0, $unpaidOutboundTotal - $unpaidOutboundPaid);

        $overdueOutboundCount = OutboundInvoice::whereIn('status', ['Unpaid', 'Partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        // 3. Hutang ke Supplier (Unpaid & Partial)
        $unpaidInboundQuery = InboundInvoice::whereIn('status', ['Unpaid', 'Partial']);
        $unpaidInboundTotal = (float) $unpaidInboundQuery->sum('total_amount');
        $unpaidInboundPaid = (float) $unpaidInboundQuery->sum('paid_amount');
        $hutangSupplier = max(0, $unpaidInboundTotal - $unpaidInboundPaid);

        $unpaidInboundCount = $unpaidInboundQuery->count();

        // 4. Pengeluaran Pembelian & Margin Kas Bersih Bulan Ini
        $inboundExpense = InboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total_amount');

        $marginKas = $outboundRevenue - $inboundExpense;

        return [
            Stat::make('Omset Penjualan (Bulan Ini)', 'Rp '.number_format($outboundRevenue, 0, ',', '.'))
                ->description($outboundCount.' Faktur Penjualan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Piutang Klien', 'Rp '.number_format($piutangKlien, 0, ',', '.'))
                ->description(
                    $overdueOutboundCount > 0
                        ? $overdueOutboundCount.' Faktur Jatuh Tempo'
                        : 'Belum lunas / tempo'
                )
                ->descriptionIcon($overdueOutboundCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
                ->color($overdueOutboundCount > 0 ? 'danger' : 'warning'),

            Stat::make('Hutang ke Supplier', 'Rp '.number_format($hutangSupplier, 0, ',', '.'))
                ->description($unpaidInboundCount.' Faktur Belum Dibayar')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Estimasi Margin Kas', 'Rp '.number_format($marginKas, 0, ',', '.'))
                ->description('Pemasukan - Pembelian Bulan Ini')
                ->descriptionIcon($marginKas >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-arrow-trending-down')
                ->color($marginKas >= 0 ? 'primary' : 'danger'),
        ];
    }
}
