<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PurchaseMonthlyChart;
use Filament\Pages\Page;

class GrafikPembelian extends Page
{
    protected static ?string $navigationGroup = 'Grafik & Analitik';

    protected static ?string $navigationLabel = 'Grafik Pembelian';

    protected static ?string $title = 'Grafik & Analitik Pembelian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static string $view = 'filament.pages.grafik-pembelian';

    protected function getHeaderWidgets(): array
    {
        return [
            PurchaseMonthlyChart::class,
        ];
    }
}
