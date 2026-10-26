<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SalesMonthlyChart;
use Filament\Pages\Page;

class GrafikPenjualan extends Page
{
    protected static ?string $navigationGroup = 'Grafik & Analitik';

    protected static ?string $navigationLabel = 'Grafik Penjualan';

    protected static ?string $title = 'Grafik & Analitik Penjualan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.grafik-penjualan';

    protected function getHeaderWidgets(): array
    {
        return [
            SalesMonthlyChart::class,
        ];
    }
}
