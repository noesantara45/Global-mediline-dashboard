<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $outboundRevenue = OutboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total_amount');

        $inboundExpense = InboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total_amount');

        $outboundCount = OutboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->count();

        $inboundCount = InboundInvoice::whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->count();

        return [
            Stat::make('Total Pemasukan (Bulan Ini)', 'Rp '.number_format($outboundRevenue, 0, ',', '.'))
                ->description('Dari '.$outboundCount.' Faktur Penjualan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pengeluaran (Bulan Ini)', 'Rp '.number_format($inboundExpense, 0, ',', '.'))
                ->description('Dari '.$inboundCount.' Faktur Pembelian')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Total Margin Kas Kasar', 'Rp '.number_format($outboundRevenue - $inboundExpense, 0, ',', '.'))
                ->description('Pemasukan dikurangi Pengeluaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
