<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InboundInvoiceResource;
use App\Filament\Resources\OutboundInvoiceResource;
use App\Filament\Widgets\IncomeExpenseChart;
use App\Filament\Widgets\InvoiceStatusChart;
use App\Filament\Widgets\LatestInboundInvoices;
use App\Filament\Widgets\LatestOutboundInvoices;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    public function getSubheading(): ?string
    {
        $userName = auth()->user()?->name ?? 'Admin';

        return 'Selamat datang kembali, '.$userName.'. Ringkasan omzet, arus kas, dan tagihan operasional hari ini.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_inbound')
                ->label('Input Pembelian (OCR)')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->size('sm')
                ->url(fn (): string => InboundInvoiceResource::getUrl('create')),

            Action::make('create_outbound')
                ->label('Buat Faktur Jual')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->size('sm')
                ->url(fn (): string => OutboundInvoiceResource::getUrl('create')),
        ];
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            IncomeExpenseChart::class,
            InvoiceStatusChart::class,
            OverdueInvoicesWidget::class,
            LatestOutboundInvoices::class,
            LatestInboundInvoices::class,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 6,
        ];
    }
}
