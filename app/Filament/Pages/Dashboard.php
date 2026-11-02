<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceStatusChart;
use App\Filament\Widgets\LatestInboundInvoices;
use App\Filament\Widgets\LatestOutboundInvoices;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): ?string
    {
        $userName = auth()->user()?->name ?? 'Admin';

        return 'Selamat datang kembali, '.$userName.'. Pantau arus kas, status piutang klien, dan faktur operasional Anda secara real-time.';
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            OverdueInvoicesWidget::class,
            InvoiceStatusChart::class,
            LatestOutboundInvoices::class,
            LatestInboundInvoices::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
